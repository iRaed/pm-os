<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Leasing\Http\Controllers\LeaseController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {

    Route::apiResource('leases', LeaseController::class);

    Route::prefix('leases/{lease}')->group(function () {
        Route::post('/activate', [LeaseController::class, 'activate']);
        Route::post('/terminate', [LeaseController::class, 'terminate']);
        Route::post('/renew', [LeaseController::class, 'renew']);
        Route::get('/payment-schedule', [LeaseController::class, 'paymentSchedule']);
        Route::post('/generate-invoices', [LeaseController::class, 'generateInvoices']);
    });

    Route::get('leases-expiring', [LeaseController::class, 'expiring']);
    Route::get('leases-stats', [LeaseController::class, 'stats']);
});
