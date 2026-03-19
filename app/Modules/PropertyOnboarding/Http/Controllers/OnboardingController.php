<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Models\Property;
use Modules\PropertyOnboarding\Http\Requests\CreatePropertyRequest;
use Modules\PropertyOnboarding\Http\Requests\AddUnitsRequest;
use Modules\PropertyOnboarding\Http\Requests\SaveInspectionRequest;
use Modules\PropertyOnboarding\Http\Requests\SaveRiskAssessmentRequest;
use Modules\PropertyOnboarding\Http\Requests\GenerateHandoverRequest;
use Modules\PropertyOnboarding\Http\Requests\UploadDocumentsRequest;
use Modules\PropertyOnboarding\Services\PropertyOnboardingService;

class OnboardingController extends Controller
{
    public function __construct(
        private PropertyOnboardingService $service
    ) {}

    /**
     * الخطوة ١: إنشاء عقار جديد
     */
    public function createProperty(CreatePropertyRequest $request): JsonResponse
    {
        $property = $this->service->createProperty(
            $request->except('owner_id'),
            $request->input('owner_id')
        );

        return response()->json([
            'message' => 'تم إنشاء العقار بنجاح — ابدأ التهيئة',
            'data' => [
                'property' => $property,
                'onboarding' => $this->service->getOnboardingStatus($property),
            ],
        ], 201);
    }

    /**
     * عرض حالة التهيئة
     */
    public function status(Property $property): JsonResponse
    {
        return response()->json([
            'data' => [
                'property' => $property->load(['owner', 'manager', 'units']),
                'onboarding' => $this->service->getOnboardingStatus($property),
            ],
        ]);
    }

    /**
     * الخطوة ٢: تأكيد بيانات المالك
     */
    public function confirmOwner(Property $property): JsonResponse
    {
        if (!$property->owner) {
            return response()->json(['message' => 'المالك غير محدد'], 422);
        }

        $this->service->completeStep($property, 'owner_info');

        return response()->json([
            'message' => 'تم تأكيد بيانات المالك',
            'data' => $this->service->getOnboardingStatus($property->fresh()),
        ]);
    }

    /**
     * الخطوة ٣: رفع الوثائق
     */
    public function uploadDocuments(UploadDocumentsRequest $request, Property $property): JsonResponse
    {
        $uploaded = [];

        foreach ($request->file('documents', []) as $index => $file) {
            $category = $request->input("categories.{$index}", 'other');
            $title = $request->input("titles.{$index}", $file->getClientOriginalName());

            $path = $file->store("properties/{$property->id}/documents", 's3');

            $doc = $property->documents()->create([
                'category' => $category,
                'title' => $title,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => auth()->id(),
            ]);

            $uploaded[] = $doc;
        }

        // إذا فيه صك على الأقل — نكمل الخطوة
        $hasDeed = $property->documents()->where('category', 'deed')->exists();
        if ($hasDeed) {
            $this->service->completeStep($property, 'documents');
        }

        return response()->json([
            'message' => 'تم رفع ' . count($uploaded) . ' مستند',
            'data' => [
                'documents' => $uploaded,
                'onboarding' => $this->service->getOnboardingStatus($property->fresh()),
                'deed_uploaded' => $hasDeed,
            ],
        ]);
    }

    /**
     * الخطوة ٤: إضافة الوحدات
     */
    public function addUnits(AddUnitsRequest $request, Property $property): JsonResponse
    {
        $units = $this->service->addUnits($property, $request->input('units'));

        return response()->json([
            'message' => 'تم إضافة ' . count($units) . ' وحدة',
            'data' => [
                'units' => $units,
                'total_units' => $property->fresh()->total_units,
                'onboarding' => $this->service->getOnboardingStatus($property->fresh()),
            ],
        ]);
    }

    /**
     * إضافة وحدات بشكل مجمّع (Bulk)
     */
    public function bulkAddUnits(Request $request, Property $property): JsonResponse
    {
        $request->validate([
            'floors' => 'required|integer|min:1|max:100',
            'units_per_floor' => 'required|integer|min:1|max:50',
            'type' => 'required|string',
            'base_rent' => 'nullable|numeric|min:0',
            'area_sqm' => 'nullable|numeric|min:0',
            'rooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
        ]);

        $floors = $request->input('floors');
        $perFloor = $request->input('units_per_floor');
        $unitsData = [];

        for ($f = 1; $f <= $floors; $f++) {
            for ($u = 1; $u <= $perFloor; $u++) {
                $unitNumber = ($f * 100) + $u;
                $unitsData[] = [
                    'unit_number' => (string) $unitNumber,
                    'floor' => $f,
                    'type' => $request->input('type'),
                    'status' => 'vacant',
                    'base_rent' => $request->input('base_rent'),
                    'area_sqm' => $request->input('area_sqm'),
                    'rooms' => $request->input('rooms'),
                    'bathrooms' => $request->input('bathrooms'),
                ];
            }
        }

        $units = $this->service->addUnits($property, $unitsData);

        return response()->json([
            'message' => "تم إنشاء " . count($units) . " وحدة ({$floors} طوابق × {$perFloor} وحدات)",
            'data' => [
                'created_count' => count($units),
                'total_units' => $property->fresh()->total_units,
                'onboarding' => $this->service->getOnboardingStatus($property->fresh()),
            ],
        ]);
    }

    /**
     * الحصول على Checklist الافتراضي
     */
    public function getChecklist(Property $property): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getDefaultChecklist($property->type),
        ]);
    }

    /**
     * الخطوة ٥: حفظ المعاينة الميدانية
     */
    public function saveInspection(SaveInspectionRequest $request, Property $property): JsonResponse
    {
        $inspection = $this->service->saveInspection($property, $request->validated());

        return response()->json([
            'message' => 'تم حفظ نتائج المعاينة',
            'data' => [
                'inspection' => $inspection,
                'onboarding' => $this->service->getOnboardingStatus($property->fresh()),
            ],
        ]);
    }

    /**
     * الخطوة ٦: تقييم المخاطر
     */
    public function saveRiskAssessment(SaveRiskAssessmentRequest $request, Property $property): JsonResponse
    {
        $this->service->saveRiskAssessment($property, $request->input('risks'));

        return response()->json([
            'message' => 'تم حفظ تقييم المخاطر',
            'data' => [
                'risk_level' => $property->fresh()->risk_level,
                'risks_count' => $property->risks()->count(),
                'onboarding' => $this->service->getOnboardingStatus($property->fresh()),
            ],
        ]);
    }

    /**
     * الخطوة ٧: محضر التسليم
     */
    public function generateHandover(GenerateHandoverRequest $request, Property $property): JsonResponse
    {
        $handover = $this->service->generateHandover($property, $request->validated());

        $property->refresh();

        return response()->json([
            'message' => $property->status === Property::STATUS_ACTIVE
                ? 'تم إكمال التهيئة وتفعيل العقار'
                : 'تم إنشاء محضر التسليم',
            'data' => [
                'handover' => $handover,
                'property_status' => $property->status,
                'onboarding' => $this->service->getOnboardingStatus($property),
            ],
        ]);
    }
}
