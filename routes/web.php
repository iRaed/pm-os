<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes (SaaS Admin)
|--------------------------------------------------------------------------
| لإدارة الشركات والاشتراكات — النطاق المركزي فقط
|
| These paths (e.g. "/") are also defined for tenants in routes/tenant.php.
| To keep both working, central routes are bound to the central domains so
| they only match there; tenant routes (no domain constraint) then handle
| every tenant subdomain. Without this, the later-registered tenant routes
| would shadow the central ones for shared paths like "/".
*/

$centralDomains = config('tenancy.central_domains', []);

foreach ($centralDomains as $centralDomain) {
    Route::domain($centralDomain)->middleware(['web'])->group(function () {
        Route::get('/', function () {
            return inertia('Central/Landing');
        });

        Route::get('/pricing', function () {
            $plans = \App\Core\MultiTenancy\Models\Plan::active()->get();

            return inertia('Central/Pricing', ['plans' => $plans]);
        });
    });

    // Central Admin
    Route::domain($centralDomain)
        ->middleware(['web', 'auth', 'central_admin'])
        ->prefix('admin')
        ->group(function () {
            Route::get('/dashboard', function () {
                return inertia('Central/Admin/Dashboard');
            })->name('central.dashboard');

            Route::get('/tenants', function () {
                return inertia('Central/Admin/Tenants');
            })->name('central.tenants');
        });
}
