<?php

declare(strict_types=1);

namespace App\Jobs\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Leasing\Models\Lease;

/**
 * فحص العقود القريبة من الانتهاء وإرسال تنبيهات
 */
class CheckLeaseExpirations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $alertDays = [90, 60, 30, 14, 7];

        foreach ($alertDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $expiringLeases = Lease::where('status', Lease::STATUS_ACTIVE)
                ->whereDate('end_date', $targetDate)
                ->with(['unit.property', 'resident'])
                ->get();

            foreach ($expiringLeases as $lease) {
                // تجنب التكرار
                $existingAlert = $lease->alerts()
                    ->where('type', 'lease_expiry')
                    ->whereDate('trigger_date', now())
                    ->exists();

                if ($existingAlert) continue;

                $severity = $days <= 14 ? 'critical' : ($days <= 30 ? 'warning' : 'info');

                $lease->alerts()->create([
                    'type' => 'lease_expiry',
                    'severity' => $severity,
                    'title' => "عقد ينتهي خلال {$days} يوم",
                    'message' => "عقد المستأجر {$lease->resident->name} للوحدة {$lease->unit->unit_number} في {$lease->unit->property->name} ينتهي بتاريخ {$lease->end_date->format('Y-m-d')}",
                    'trigger_date' => now(),
                    'assigned_to_role' => 'property_manager',
                    'is_auto_generated' => true,
                    'actions' => [
                        ['label' => 'تجديد العقد', 'action' => 'renew_lease', 'lease_id' => $lease->id],
                        ['label' => 'التواصل مع المستأجر', 'action' => 'contact_resident', 'resident_id' => $lease->resident_id],
                    ],
                    'metadata' => [
                        'lease_id' => $lease->id,
                        'days_remaining' => $days,
                        'resident_name' => $lease->resident->name,
                        'unit' => $lease->unit->unit_number,
                        'property' => $lease->unit->property->name,
                    ],
                ]);

                // تحديث حالة العقد إذا <= 30 يوم
                if ($days <= 30 && $lease->status === Lease::STATUS_ACTIVE) {
                    $lease->update(['status' => Lease::STATUS_EXPIRING]);
                }
            }

            logger()->info("CheckLeaseExpirations: Found {$expiringLeases->count()} leases expiring in {$days} days");
        }
    }
}
