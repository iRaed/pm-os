<?php

declare(strict_types=1);

namespace App\Jobs\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Maintenance\Models\PmPlan;
use Modules\Maintenance\Models\WorkOrder;
use Modules\Maintenance\Models\PmLog;

/**
 * فحص خطط الصيانة الوقائية المستحقة وإنشاء أوامر عمل تلقائية
 */
class TriggerPreventiveMaintenance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $duePlans = PmPlan::due()
            ->where('auto_create_wo', true)
            ->with(['property', 'contractor', 'assignedUser'])
            ->get();

        foreach ($duePlans as $plan) {
            // إنشاء أمر عمل
            $workOrder = WorkOrder::create([
                'property_id' => $plan->property_id,
                'reported_by_type' => 'system',
                'wo_number' => WorkOrder::generateWoNumber(),
                'title' => "صيانة وقائية: {$plan->name}",
                'description' => $plan->instructions ?? "تنفيذ خطة الصيانة الوقائية: {$plan->name} — {$plan->asset_type} ({$plan->asset_identifier})",
                'category' => $this->mapAssetTypeToCategory($plan->asset_type),
                'priority' => 'medium',
                'status' => $plan->contractor_id ? 'assigned' : 'open',
                'assigned_to_type' => $plan->contractor_id ? 'contractor' : ($plan->assigned_user_id ? 'staff' : null),
                'assigned_to_id' => $plan->assigned_user_id,
                'contractor_id' => $plan->contractor_id,
                'estimated_cost' => $plan->estimated_cost,
                'scheduled_date' => $plan->next_scheduled_date,
                'sla_deadline' => now()->addDays(7),
                'pm_plan_id' => $plan->id,
                'is_preventive' => true,
                'checklist' => $plan->checklist,
            ]);

            // إنشاء سجل في PM Logs
            PmLog::create([
                'pm_plan_id' => $plan->id,
                'work_order_id' => $workOrder->id,
                'scheduled_date' => $plan->next_scheduled_date,
            ]);

            // تحديث تاريخ الصيانة القادم
            $plan->update([
                'last_executed_date' => $plan->next_scheduled_date,
                'next_scheduled_date' => $plan->calculateNextDate(),
            ]);
        }

        logger()->info("TriggerPreventiveMaintenance: Created {$duePlans->count()} work orders");
    }

    private function mapAssetTypeToCategory(string $assetType): string
    {
        return match ($assetType) {
            'elevator' => WorkOrder::CATEGORY_ELEVATOR,
            'generator' => WorkOrder::CATEGORY_ELECTRICAL,
            'hvac' => WorkOrder::CATEGORY_HVAC,
            'fire_system' => WorkOrder::CATEGORY_FIRE_SYSTEM,
            'water_tank', 'pool' => WorkOrder::CATEGORY_PLUMBING,
            'electrical_panel' => WorkOrder::CATEGORY_ELECTRICAL,
            default => WorkOrder::CATEGORY_GENERAL,
        };
    }
}
