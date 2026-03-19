<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Services;

use Illuminate\Support\Facades\DB;
use Modules\Foundation\Models\Property;
use Modules\Foundation\Models\Unit;
use Modules\Foundation\Models\Owner;
use Modules\PropertyOnboarding\Events\PropertyOnboarded;
use Modules\PropertyOnboarding\Events\OnboardingStepCompleted;

/**
 * خدمة تهيئة العقار للإدارة
 * تدير الخطوات من الاستلام إلى التفعيل
 */
class PropertyOnboardingService
{
    /**
     * الخطوات المطلوبة للتهيئة
     */
    const STEPS = [
        'property_info',    // ١. بيانات العقار الأساسية
        'owner_info',       // ٢. بيانات المالك
        'documents',        // ٣. الوثائق والمستندات
        'units',            // ٤. إنشاء الوحدات
        'inspection',       // ٥. المعاينة الميدانية
        'risk_assessment',  // ٦. تقييم المخاطر
        'handover',         // ٧. محضر التسليم
    ];

    /**
     * إنشاء عقار جديد مع بدء التهيئة
     */
    public function createProperty(array $data, string $ownerId): Property
    {
        return DB::transaction(function () use ($data, $ownerId) {
            $property = Property::create([
                'owner_id' => $ownerId,
                'code' => Property::generateCode(),
                'status' => Property::STATUS_ONBOARDING,
                'onboarding_date' => now(),
                ...$data,
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'onboarding' => [
                        'started_at' => now()->toISOString(),
                        'completed_steps' => ['property_info'],
                        'current_step' => 'owner_info',
                    ],
                ]),
            ]);

            event(new OnboardingStepCompleted($property, 'property_info'));

            return $property;
        });
    }

    /**
     * الحصول على حالة التهيئة
     */
    public function getOnboardingStatus(Property $property): array
    {
        $meta = $property->metadata['onboarding'] ?? [];
        $completedSteps = $meta['completed_steps'] ?? [];

        $steps = collect(self::STEPS)->map(function ($step) use ($completedSteps, $meta) {
            $isCompleted = in_array($step, $completedSteps);
            $isCurrent = ($meta['current_step'] ?? '') === $step;

            return [
                'key' => $step,
                'label' => $this->getStepLabel($step),
                'label_en' => $this->getStepLabelEn($step),
                'status' => $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'pending'),
                'icon' => $this->getStepIcon($step),
            ];
        });

        $completedCount = count($completedSteps);
        $totalSteps = count(self::STEPS);

        return [
            'steps' => $steps,
            'progress' => $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0,
            'completed_count' => $completedCount,
            'total_steps' => $totalSteps,
            'current_step' => $meta['current_step'] ?? self::STEPS[0],
            'is_complete' => $completedCount >= $totalSteps,
            'started_at' => $meta['started_at'] ?? null,
        ];
    }

    /**
     * إكمال خطوة في التهيئة
     */
    public function completeStep(Property $property, string $step): Property
    {
        $meta = $property->metadata ?? [];
        $onboarding = $meta['onboarding'] ?? ['completed_steps' => [], 'started_at' => now()->toISOString()];

        $completedSteps = $onboarding['completed_steps'] ?? [];

        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        // تحديد الخطوة التالية
        $nextStep = null;
        foreach (self::STEPS as $s) {
            if (!in_array($s, $completedSteps)) {
                $nextStep = $s;
                break;
            }
        }

        $onboarding['completed_steps'] = $completedSteps;
        $onboarding['current_step'] = $nextStep;
        $onboarding["step_{$step}_completed_at"] = now()->toISOString();
        $meta['onboarding'] = $onboarding;

        $property->update(['metadata' => $meta]);

        event(new OnboardingStepCompleted($property, $step));

        // إذا كل الخطوات مكتملة
        if ($nextStep === null) {
            $this->finalizeOnboarding($property);
        }

        return $property->fresh();
    }

    /**
     * إضافة وحدات دفعة واحدة
     */
    public function addUnits(Property $property, array $unitsData): array
    {
        $created = [];

        DB::transaction(function () use ($property, $unitsData, &$created) {
            foreach ($unitsData as $unitData) {
                $created[] = Unit::create([
                    'property_id' => $property->id,
                    'unit_code' => "{$property->code}-U-{$unitData['unit_number']}",
                    ...$unitData,
                ]);
            }

            // تحديث عدد الوحدات في العقار
            $property->update([
                'total_units' => $property->units()->count(),
            ]);
        });

        $this->completeStep($property, 'units');

        return $created;
    }

    /**
     * حفظ نتائج المعاينة الميدانية
     */
    public function saveInspection(Property $property, array $inspectionData): \Modules\Maintenance\Models\Inspection
    {
        $inspection = $property->inspections()->create([
            'type' => 'onboarding',
            'status' => 'completed',
            'inspector_id' => auth()->id(),
            'scheduled_date' => now(),
            'completed_date' => now(),
            'checklist' => $inspectionData['checklist'] ?? [],
            'results' => $inspectionData['results'] ?? [],
            'overall_rating' => $inspectionData['overall_rating'] ?? null,
            'photos' => $inspectionData['photos'] ?? [],
            'findings' => $inspectionData['findings'] ?? null,
            'recommendations' => $inspectionData['recommendations'] ?? null,
        ]);

        $this->completeStep($property, 'inspection');

        return $inspection;
    }

    /**
     * حفظ تقييم المخاطر
     */
    public function saveRiskAssessment(Property $property, array $risks): void
    {
        foreach ($risks as $riskData) {
            $property->risks()->create([
                'category' => $riskData['category'],
                'title' => $riskData['title'],
                'description' => $riskData['description'] ?? null,
                'likelihood' => $riskData['likelihood'],
                'impact' => $riskData['impact'],
                'risk_score' => $this->calculateRiskScore($riskData['likelihood'], $riskData['impact']),
                'mitigation_plan' => $riskData['mitigation_plan'] ?? null,
                'status' => 'identified',
                'owner_id' => auth()->id(),
                'review_date' => now()->addMonths(3),
            ]);
        }

        // تحديث مستوى المخاطر الإجمالي
        $maxScore = $property->risks()->max('risk_score') ?? 0;
        $property->update([
            'risk_level' => match (true) {
                $maxScore >= 20 => 'critical',
                $maxScore >= 12 => 'high',
                $maxScore >= 6 => 'medium',
                default => 'low',
            },
        ]);

        $this->completeStep($property, 'risk_assessment');
    }

    /**
     * إنشاء محضر التسليم
     */
    public function generateHandover(Property $property, array $handoverData): array
    {
        $meta = $property->metadata ?? [];
        $meta['handover'] = [
            'generated_at' => now()->toISOString(),
            'generated_by' => auth()->id(),
            'notes' => $handoverData['notes'] ?? null,
            'signed_by_company' => $handoverData['signed_by_company'] ?? null,
            'signed_by_owner' => $handoverData['signed_by_owner'] ?? null,
            'signed_at' => $handoverData['signed_at'] ?? now()->toISOString(),
            'witnesses' => $handoverData['witnesses'] ?? [],
        ];

        $property->update(['metadata' => $meta]);

        $this->completeStep($property, 'handover');

        return $meta['handover'];
    }

    /**
     * إتمام التهيئة وتفعيل العقار
     */
    private function finalizeOnboarding(Property $property): void
    {
        $property->update([
            'status' => Property::STATUS_ACTIVE,
            'operation_start_date' => now(),
        ]);

        $meta = $property->metadata ?? [];
        $meta['onboarding']['completed_at'] = now()->toISOString();
        $property->update(['metadata' => $meta]);

        event(new PropertyOnboarded($property));
    }

    /**
     * قائمة فحص المعاينة الافتراضية حسب نوع العقار
     */
    public function getDefaultChecklist(string $propertyType): array
    {
        $common = [
            ['id' => 'ext_walls', 'label' => 'حالة الجدران الخارجية', 'label_en' => 'External walls condition', 'category' => 'structural'],
            ['id' => 'ext_paint', 'label' => 'حالة الدهان الخارجي', 'label_en' => 'External paint', 'category' => 'structural'],
            ['id' => 'roof', 'label' => 'حالة السطح والعزل', 'label_en' => 'Roof & insulation', 'category' => 'structural'],
            ['id' => 'entrance', 'label' => 'المدخل الرئيسي', 'label_en' => 'Main entrance', 'category' => 'structural'],
            ['id' => 'parking', 'label' => 'المواقف', 'label_en' => 'Parking area', 'category' => 'structural'],
            ['id' => 'elec_main', 'label' => 'اللوحة الكهربائية الرئيسية', 'label_en' => 'Main electrical panel', 'category' => 'electrical'],
            ['id' => 'elec_meters', 'label' => 'العدادات الكهربائية', 'label_en' => 'Electrical meters', 'category' => 'electrical'],
            ['id' => 'lighting', 'label' => 'الإنارة العامة', 'label_en' => 'Common area lighting', 'category' => 'electrical'],
            ['id' => 'water_main', 'label' => 'شبكة المياه الرئيسية', 'label_en' => 'Main water supply', 'category' => 'plumbing'],
            ['id' => 'water_tanks', 'label' => 'خزانات المياه', 'label_en' => 'Water tanks', 'category' => 'plumbing'],
            ['id' => 'sewage', 'label' => 'شبكة الصرف الصحي', 'label_en' => 'Sewage system', 'category' => 'plumbing'],
            ['id' => 'fire_alarm', 'label' => 'نظام إنذار الحريق', 'label_en' => 'Fire alarm system', 'category' => 'fire_safety'],
            ['id' => 'fire_ext', 'label' => 'طفايات الحريق', 'label_en' => 'Fire extinguishers', 'category' => 'fire_safety'],
            ['id' => 'fire_exit', 'label' => 'مخارج الطوارئ', 'label_en' => 'Emergency exits', 'category' => 'fire_safety'],
            ['id' => 'security', 'label' => 'نظام الأمن والكاميرات', 'label_en' => 'Security & CCTV', 'category' => 'security'],
            ['id' => 'intercom', 'label' => 'نظام الاتصال الداخلي', 'label_en' => 'Intercom system', 'category' => 'security'],
        ];

        $typeSpecific = match ($propertyType) {
            'residential_compound', 'tower' => [
                ['id' => 'elevator', 'label' => 'المصاعد', 'label_en' => 'Elevators', 'category' => 'mechanical'],
                ['id' => 'generator', 'label' => 'المولد الاحتياطي', 'label_en' => 'Backup generator', 'category' => 'mechanical'],
                ['id' => 'hvac_central', 'label' => 'التكييف المركزي', 'label_en' => 'Central HVAC', 'category' => 'mechanical'],
                ['id' => 'pool', 'label' => 'المسبح', 'label_en' => 'Swimming pool', 'category' => 'amenities'],
                ['id' => 'gym', 'label' => 'الصالة الرياضية', 'label_en' => 'Gym', 'category' => 'amenities'],
                ['id' => 'garden', 'label' => 'الحدائق والمسطحات', 'label_en' => 'Gardens & landscaping', 'category' => 'amenities'],
                ['id' => 'playground', 'label' => 'ألعاب الأطفال', 'label_en' => 'Playground', 'category' => 'amenities'],
            ],
            'mall', 'commercial_building' => [
                ['id' => 'elevator', 'label' => 'المصاعد', 'label_en' => 'Elevators', 'category' => 'mechanical'],
                ['id' => 'escalator', 'label' => 'السلالم الكهربائية', 'label_en' => 'Escalators', 'category' => 'mechanical'],
                ['id' => 'hvac_central', 'label' => 'التكييف المركزي', 'label_en' => 'Central HVAC', 'category' => 'mechanical'],
                ['id' => 'loading_dock', 'label' => 'منطقة التحميل', 'label_en' => 'Loading dock', 'category' => 'operational'],
                ['id' => 'signage', 'label' => 'اللوحات والإرشادات', 'label_en' => 'Signage & wayfinding', 'category' => 'operational'],
            ],
            'villa' => [
                ['id' => 'garden', 'label' => 'الحديقة', 'label_en' => 'Garden', 'category' => 'amenities'],
                ['id' => 'pool', 'label' => 'المسبح', 'label_en' => 'Pool', 'category' => 'amenities'],
                ['id' => 'boundary', 'label' => 'السور والبوابة', 'label_en' => 'Boundary wall & gate', 'category' => 'structural'],
                ['id' => 'driver_room', 'label' => 'غرفة السائق', 'label_en' => 'Driver room', 'category' => 'structural'],
            ],
            'warehouse' => [
                ['id' => 'loading_dock', 'label' => 'منطقة التحميل', 'label_en' => 'Loading dock', 'category' => 'operational'],
                ['id' => 'floor_load', 'label' => 'قدرة تحمّل الأرضية', 'label_en' => 'Floor load capacity', 'category' => 'structural'],
                ['id' => 'ventilation', 'label' => 'التهوية', 'label_en' => 'Ventilation', 'category' => 'mechanical'],
                ['id' => 'roller_doors', 'label' => 'أبواب الرول', 'label_en' => 'Roller doors', 'category' => 'structural'],
            ],
            default => [],
        };

        return array_merge($common, $typeSpecific);
    }

    // ─── Helpers ─────────────────────────────────────

    private function calculateRiskScore(string $likelihood, string $impact): int
    {
        $likelihoodMap = ['rare' => 1, 'unlikely' => 2, 'possible' => 3, 'likely' => 4, 'certain' => 5];
        $impactMap = ['negligible' => 1, 'minor' => 2, 'moderate' => 3, 'major' => 4, 'catastrophic' => 5];

        return ($likelihoodMap[$likelihood] ?? 1) * ($impactMap[$impact] ?? 1);
    }

    private function getStepLabel(string $step): string
    {
        return match ($step) {
            'property_info' => 'بيانات العقار',
            'owner_info' => 'بيانات المالك',
            'documents' => 'الوثائق والمستندات',
            'units' => 'إنشاء الوحدات',
            'inspection' => 'المعاينة الميدانية',
            'risk_assessment' => 'تقييم المخاطر',
            'handover' => 'محضر التسليم',
            default => $step,
        };
    }

    private function getStepLabelEn(string $step): string
    {
        return match ($step) {
            'property_info' => 'Property Information',
            'owner_info' => 'Owner Information',
            'documents' => 'Documents',
            'units' => 'Create Units',
            'inspection' => 'Field Inspection',
            'risk_assessment' => 'Risk Assessment',
            'handover' => 'Handover Report',
            default => $step,
        };
    }

    private function getStepIcon(string $step): string
    {
        return match ($step) {
            'property_info' => '🏢',
            'owner_info' => '👤',
            'documents' => '📄',
            'units' => '🏠',
            'inspection' => '🔍',
            'risk_assessment' => '⚠️',
            'handover' => '✅',
            default => '📋',
        };
    }
}
