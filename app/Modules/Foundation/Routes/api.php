<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Foundation\Http\Controllers\PropertyController;
use Modules\Foundation\Http\Controllers\UnitController;
use Modules\Foundation\Http\Controllers\OwnerController;
use Modules\Foundation\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Foundation Module API Routes
|--------------------------------------------------------------------------
| Prefix: /api/v1
| Middleware: auth:sanctum, tenant
*/

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {

    // ─── Dashboard ───────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // ─── العقارات ────────────────────────────────────
    Route::apiResource('properties', PropertyController::class);
    Route::prefix('properties/{property}')->group(function () {
        Route::get('/units', [PropertyController::class, 'units']);
        Route::get('/stats', [PropertyController::class, 'stats']);
        Route::get('/occupancy', [PropertyController::class, 'occupancy']);
        Route::get('/financial-summary', [PropertyController::class, 'financialSummary']);
        Route::post('/onboard', [PropertyController::class, 'completeOnboarding']);
    });

    // ─── الوحدات ─────────────────────────────────────
    Route::apiResource('units', UnitController::class);
    Route::get('units/{unit}/lease-history', [UnitController::class, 'leaseHistory']);
    Route::get('units/{unit}/maintenance-history', [UnitController::class, 'maintenanceHistory']);

    // ─── الملاك ──────────────────────────────────────
    Route::apiResource('owners', OwnerController::class);
    Route::get('owners/{owner}/properties', [OwnerController::class, 'properties']);
    Route::get('owners/{owner}/statement', [OwnerController::class, 'statement']);
});
