<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Collection\Http\Controllers\InvoiceController;
use Modules\Collection\Http\Controllers\PaymentController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::post('invoices/{invoice}/write-off', [InvoiceController::class, 'writeOff']);
    Route::get('invoices-overdue', [InvoiceController::class, 'overdue']);
    Route::get('invoices-aging', [InvoiceController::class, 'aging']);

    // Payments
    Route::apiResource('payments', PaymentController::class)->except(['update', 'destroy']);
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm']);
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);

    // Collection Dashboard
    Route::get('collection/dashboard', [InvoiceController::class, 'dashboard']);
    Route::get('collection/aging-report', [InvoiceController::class, 'agingReport']);
});
