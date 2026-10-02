<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Stancl\Tenancy\Facades\Tenancy;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| Migrated from app/Console/Kernel.php — Laravel 11 defines the schedule
| here (or via ->withSchedule() in bootstrap/app.php) instead of a Kernel.
*/

// ─── Central Jobs (SaaS Level) ───────────────
Schedule::command('tenants:check-subscriptions')
    ->daily()
    ->at('01:00')
    ->description('فحص انتهاء اشتراكات الشركات');

// ─── Tenant Jobs (Per Company) — daily ───────────────
Schedule::call(function () {
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

// ─── Tenant Jobs — weekly ───────────────
Schedule::call(function () {
    Tenancy::runForMultiple(null, function () {
        dispatch(new \App\Jobs\Tenant\GenerateWeeklyReport);
        dispatch(new \App\Jobs\Tenant\CheckInsuranceExpiry);
    });
})->weeklyOn(0, '08:00')->description('التقارير الأسبوعية');

// ─── Tenant Jobs — monthly ───────────────
Schedule::call(function () {
    Tenancy::runForMultiple(null, function () {
        dispatch(new \App\Jobs\Tenant\GenerateMonthlyInvoices);
        dispatch(new \App\Jobs\Tenant\GenerateOwnerStatements);
        dispatch(new \App\Jobs\Tenant\GenerateMonthlyReport);
    });
})->monthlyOn(1, '05:00')->description('المهام الشهرية — الفواتير وكشوف الملاك');
