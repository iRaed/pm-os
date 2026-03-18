<?php

declare(strict_types=1);

namespace Modules\AIEngine\Services;

use Modules\AIEngine\Contracts\AIProviderInterface;
use Modules\Foundation\Models\Property;

/**
 * مولّد التقارير الذكية بالذكاء الاصطناعي
 * يُنتج ملخصات تنفيذية شهرية بالعربية
 */
class AIReportGenerator
{
    public function __construct(
        private AIProviderInterface $ai
    ) {}

    /**
     * إنشاء ملخص تنفيذي شهري لعقار
     */
    public function generateExecutiveSummary(Property $property, string $period): string
    {
        $data = $this->collectPropertyData($property, $period);

        $prompt = $this->buildPrompt($data);

        return $this->ai->generate($prompt, [
            'max_tokens' => 2000,
            'temperature' => 0.3,
        ]);
    }

    /**
     * تحليل المخاطر لمحفظة عقارية
     */
    public function analyzePortfolioRisks(array $propertiesData): string
    {
        $prompt = "أنت محلل مخاطر عقاري خبير. حلل البيانات التالية لمحفظة عقارية وقدّم:\n";
        $prompt .= "1. أهم 5 مخاطر مرتبة حسب الأولوية\n";
        $prompt .= "2. توصيات محددة لكل خطر\n";
        $prompt .= "3. مؤشرات إنذار مبكر يجب مراقبتها\n\n";
        $prompt .= "البيانات:\n" . json_encode($propertiesData, JSON_UNESCAPED_UNICODE);

        return $this->ai->generate($prompt, [
            'max_tokens' => 1500,
            'temperature' => 0.2,
        ]);
    }

    /**
     * توليد وصف إعلاني لوحدة شاغرة
     */
    public function generateListingDescription(array $unitData): string
    {
        $prompt = "أنت خبير تسويق عقاري. اكتب وصفاً إعلانياً جذاباً بالعربية لهذه الوحدة:\n\n";
        $prompt .= json_encode($unitData, JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "المطلوب:\n- عنوان جذاب\n- وصف من 3-4 جمل\n- أهم 5 مميزات\n- دعوة للتواصل\n";
        $prompt .= "الأسلوب: مهني وواضح، بدون مبالغة.";

        return $this->ai->generate($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);
    }

    // ─── Private Methods ─────────────────────────────

    private function collectPropertyData(Property $property, string $period): array
    {
        $units = $property->units;
        $totalUnits = $units->count();
        $occupied = $units->where('status', 'occupied')->count();

        return [
            'property_name' => $property->name,
            'period' => $period,
            'occupancy' => [
                'total_units' => $totalUnits,
                'occupied' => $occupied,
                'vacant' => $totalUnits - $occupied,
                'rate' => $totalUnits > 0 ? round(($occupied / $totalUnits) * 100, 1) : 0,
            ],
            'financial' => [
                'revenue' => $property->ledgerEntries()
                    ->where('account_type', 'revenue')->where('period', $period)->sum('credit'),
                'expenses' => $property->ledgerEntries()
                    ->where('account_type', 'expense')->where('period', $period)->sum('debit'),
            ],
            'collection' => [
                'invoiced' => $property->units->flatMap->invoices
                    ->where('period_start', '>=', $period . '-01')
                    ->sum('total_amount'),
                'collected' => $property->units->flatMap->invoices
                    ->where('status', 'paid')
                    ->where('period_start', '>=', $period . '-01')
                    ->sum('total_amount'),
                'overdue_count' => $property->units->flatMap->invoices
                    ->where('status', 'overdue')->count(),
            ],
            'maintenance' => [
                'new_work_orders' => $property->workOrders()
                    ->whereMonth('created_at', substr($period, 5, 2))
                    ->count(),
                'completed' => $property->workOrders()
                    ->whereMonth('completed_at', substr($period, 5, 2))
                    ->count(),
                'open' => $property->workOrders()->open()->count(),
                'avg_resolution_hours' => $property->workOrders()
                    ->whereNotNull('completed_at')
                    ->whereMonth('completed_at', substr($period, 5, 2))
                    ->avg(\DB::raw('EXTRACT(EPOCH FROM (completed_at - created_at)) / 3600')),
            ],
        ];
    }

    private function buildPrompt(array $data): string
    {
        return <<<PROMPT
أنت مدير أملاك محترف تكتب تقريراً شهرياً للمالك. اكتب ملخصاً تنفيذياً بالعربية الفصحى يتضمن:

1. **نظرة عامة**: وضع العقار هذا الشهر بجملتين
2. **الإشغال**: التحليل مع مقارنة (هل تحسن أو تراجع؟)
3. **المالية**: الإيرادات vs المصروفات + صافي الدخل التشغيلي
4. **التحصيل**: نسبة التحصيل + عدد المتأخرات + خطة المعالجة
5. **الصيانة**: ملخص أوامر العمل + متوسط وقت الحل
6. **التوصيات**: 3 توصيات محددة وقابلة للتنفيذ

بيانات الشهر:
العقار: {$data['property_name']}
الفترة: {$data['period']}

الإشغال: {$data['occupancy']['occupied']} من {$data['occupancy']['total_units']} ({$data['occupancy']['rate']}%)
الشواغر: {$data['occupancy']['vacant']} وحدة

الإيرادات: {$data['financial']['revenue']} ر.س
المصروفات: {$data['financial']['expenses']} ر.س

المفوتر: {$data['collection']['invoiced']} ر.س
المحصّل: {$data['collection']['collected']} ر.س
فواتير متأخرة: {$data['collection']['overdue_count']}

أوامر عمل جديدة: {$data['maintenance']['new_work_orders']}
مكتملة: {$data['maintenance']['completed']}
مفتوحة: {$data['maintenance']['open']}

اكتب التقرير بأسلوب مهني ومباشر. لا تستخدم نقاط كثيرة، اكتب بفقرات مترابطة.
PROMPT;
    }
}
