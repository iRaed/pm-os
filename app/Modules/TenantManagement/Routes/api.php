<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\TenantManagement\Http\Controllers\ResidentController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {

    Route::apiResource('residents', ResidentController::class);

    Route::prefix('residents/{resident}')->group(function () {
        Route::get('/leases', [ResidentController::class, 'leases']);
        Route::get('/invoices', [ResidentController::class, 'invoices']);
        Route::get('/payments', [ResidentController::class, 'payments']);
        Route::get('/work-orders', [ResidentController::class, 'workOrders']);
        Route::get('/statement', [ResidentController::class, 'statement']);
        Route::post('/blacklist', [ResidentController::class, 'blacklist']);
    });

    Route::get('residents-stats', [ResidentController::class, 'stats']);
});
