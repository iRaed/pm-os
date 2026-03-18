<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Http\Controllers\WorkOrderController;
use Modules\Maintenance\Http\Controllers\PmPlanController;
use Modules\Maintenance\Http\Controllers\ContractorController;
use Modules\Maintenance\Http\Controllers\InspectionController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {

    // Work Orders
    Route::apiResource('work-orders', WorkOrderController::class);
    Route::post('work-orders/{workOrder}/assign', [WorkOrderController::class, 'assign']);
    Route::post('work-orders/{workOrder}/start', [WorkOrderController::class, 'start']);
    Route::post('work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete']);
    Route::post('work-orders/{workOrder}/verify', [WorkOrderController::class, 'verify']);
    Route::post('work-orders/{workOrder}/close', [WorkOrderController::class, 'close']);
    Route::post('work-orders/{workOrder}/rate', [WorkOrderController::class, 'rate']);
    Route::get('work-orders-stats', [WorkOrderController::class, 'stats']);

    // PM Plans
    Route::apiResource('pm-plans', PmPlanController::class);
    Route::get('pm-plans-due', [PmPlanController::class, 'due']);
    Route::post('pm-plans/{pmPlan}/execute', [PmPlanController::class, 'execute']);

    // Contractors
    Route::apiResource('contractors', ContractorController::class);

    // Inspections
    Route::apiResource('inspections', InspectionController::class);
    Route::post('inspections/{inspection}/complete', [InspectionController::class, 'complete']);
});
