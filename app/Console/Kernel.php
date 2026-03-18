<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Stancl\Tenancy\Facades\Tenancy;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ─── Central Jobs (SaaS Level) ───────────────
        $schedule->command('tenants:check-subscriptions')
            ->daily()
            ->at('01:00')
            ->description('فحص انتهاء اشتراكات الشركات');

        // ─── Tenant Jobs (Per Company) ───────────────
        // كل هذه الـ Jobs تُشغّل لكل Tenant
        $schedule->call(function () {
            Tenancy::runForMultiple(null, function () {

                // يومي — التحصيل
                dispatch(new \App\Jobs\Tenant\CheckOverdueInvoices);
                dispatch(new \App\Jobs\Tenant\SendPaymentReminders);
                dispatch(new \App\Jobs\Tenant\RunCollectionEscalation);

                // يومي — العقود
                dispatch(new \App\Jobs\Tenant\CheckLeaseExpirations);
                dispatch(new \App\Jobs\Tenant\UpdateExpiredLeases);

                // يومي — الصيانة الوقائية
                dispatch(new \App\Jobs\Tenant\TriggerPreventiveMaintenance);
                dispatch(new \App\Jobs\Tenant\CheckSlaBreach);

                // يومي — التنبيهات
                dispatch(new \App\Jobs\Tenant\CheckDocumentExpiry);
                dispatch(new \App\Jobs\Tenant\CalculateOccupancyMetrics);
            });
        })->daily()->at('06:00')->description('المهام اليومية لجميع الشركات');

        // أسبوعي — التقارير
        $schedule->call(function () {
            Tenancy::runForMultiple(null, function () {
                dispatch(new \App\Jobs\Tenant\GenerateWeeklyReport);
                dispatch(new \App\Jobs\Tenant\CheckInsuranceExpiry);
            });
        })->weeklyOn(0, '08:00')->description('التقارير الأسبوعية');

        // شهري — المالية
        $schedule->call(function () {
            Tenancy::runForMultiple(null, function () {
                dispatch(new \App\Jobs\Tenant\GenerateMonthlyInvoices);
                dispatch(new \App\Jobs\Tenant\GenerateOwnerStatements);
                dispatch(new \App\Jobs\Tenant\GenerateMonthlyReport);
            });
        })->monthlyOn(1, '05:00')->description('المهام الشهرية — الفواتير وكشوف الملاك');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
