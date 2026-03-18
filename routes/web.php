<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes (SaaS Admin)
|--------------------------------------------------------------------------
| لإدارة الشركات والاشتراكات — النطاق المركزي فقط
*/

Route::middleware(['web'])->group(function () {
    Route::get('/', function () {
        return inertia('Central/Landing');
    });

    Route::get('/pricing', function () {
        $plans = \App\Core\MultiTenancy\Models\Plan::active()->get();
        return inertia('Central/Pricing', ['plans' => $plans]);
    });
});

// Central Admin API
Route::middleware(['web', 'auth', 'central_admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return inertia('Central/Admin/Dashboard');
    })->name('central.dashboard');

    Route::get('/tenants', function () {
        return inertia('Central/Admin/Tenants');
    })->name('central.tenants');
});
