<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PropertyOnboarding\Http\Controllers\OnboardingController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1/onboarding')->group(function () {

    // إنشاء عقار جديد وبدء التهيئة
    Route::post('/properties', [OnboardingController::class, 'createProperty']);

    Route::prefix('/properties/{property}')->group(function () {
        // حالة التهيئة
        Route::get('/status', [OnboardingController::class, 'status']);

        // الخطوة ٢: تأكيد المالك
        Route::post('/confirm-owner', [OnboardingController::class, 'confirmOwner']);

        // الخطوة ٣: رفع الوثائق
        Route::post('/documents', [OnboardingController::class, 'uploadDocuments']);

        // الخطوة ٤: إضافة وحدات
        Route::post('/units', [OnboardingController::class, 'addUnits']);
        Route::post('/units/bulk', [OnboardingController::class, 'bulkAddUnits']);

        // Checklist الافتراضي
        Route::get('/checklist', [OnboardingController::class, 'getChecklist']);

        // الخطوة ٥: المعاينة الميدانية
        Route::post('/inspection', [OnboardingController::class, 'saveInspection']);

        // الخطوة ٦: تقييم المخاطر
        Route::post('/risk-assessment', [OnboardingController::class, 'saveRiskAssessment']);

        // الخطوة ٧: محضر التسليم
        Route::post('/handover', [OnboardingController::class, 'generateHandover']);
    });
});
