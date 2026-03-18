<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جداول الصيانة والتشغيل — أوامر العمل والمقاولين والفحوصات
     */
    public function up(): void
    {
        // ─── المقاولون والموردون ──────────────────────
        Schema::create('contractors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('company_name')->nullable();
            $table->string('commercial_reg')->nullable();
            $table->string('phone');
            $table->string('phone_2')->nullable();
            $table->string('email')->nullable();
            $table->jsonb('specializations')->default('[]'); // plumbing, electrical, hvac, painting, etc.
            $table->decimal('rating', 3, 1)->default(0);     // 1.0 - 5.0
            $table->integer('completed_orders_count')->default(0);
            $table->string('contract_type')->default('per_job'); // per_job, retainer, framework
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->string('iban')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('status')->default('active'); // active, inactive, blacklisted
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        // ─── أوامر العمل (الصيانة) ───────────────────
        Schema::create('work_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();

            // مصدر البلاغ
            $table->string('reported_by_type')->default('staff'); // resident, staff, inspection, system
            $table->uuid('reported_by_id')->nullable();

            // التعريف
            $table->string('wo_number')->unique();       // WO-2024-00001
            $table->string('title');
            $table->text('description')->nullable();

            // التصنيف
            $table->string('category');                  // plumbing, electrical, hvac, structural, painting, appliance, pest_control, elevator, fire_system, general, other
            $table->string('sub_category')->nullable();
            $table->string('priority')->default('medium'); // emergency, high, medium, low

            // الحالة
            $table->string('status')->default('open');   // open, assigned, in_progress, on_hold, pending_parts, completed, verified, closed, cancelled

            // التعيين
            $table->string('assigned_to_type')->nullable(); // staff, contractor
            $table->uuid('assigned_to_id')->nullable();
            $table->foreignUuid('contractor_id')->nullable()->constrained('contractors')->nullOnDelete();

            // التكاليف
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->decimal('parts_cost', 12, 2)->nullable();
            $table->decimal('labor_cost', 12, 2)->nullable();

            // الأوقات
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('actual_hours', 6, 2)->nullable();
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // SLA
            $table->timestamp('sla_deadline')->nullable();
            $table->boolean('sla_breached')->default(false);

            // الصور
            $table->jsonb('before_photos')->default('[]');
            $table->jsonb('after_photos')->default('[]');

            // تقييم المستأجر
            $table->integer('resident_rating')->nullable(); // 1-5
            $table->text('resident_feedback')->nullable();

            // التحقق والإغلاق
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();

            // الربط بالصيانة الوقائية
            $table->foreignUuid('pm_plan_id')->nullable();
            $table->boolean('is_preventive')->default(false);

            // بيانات مرنة
            $table->jsonb('checklist')->default('[]');   // قائمة فحص مخصصة
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'status']);
            $table->index(['priority', 'status']);
            $table->index('category');
            $table->index('assigned_to_id');
            $table->index('sla_deadline');
        });

        // ─── خطط الصيانة الوقائية ────────────────────
        Schema::create('pm_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();

            $table->string('name');                      // اسم الخطة
            $table->string('asset_type');                // elevator, generator, hvac, fire_system, water_tank, pool, electrical_panel, plumbing, roof
            $table->string('asset_identifier')->nullable(); // رقم/موقع الأصل

            // الجدولة
            $table->string('frequency');                 // daily, weekly, monthly, quarterly, semi_annual, annual
            $table->integer('frequency_value')->default(1); // كل N وحدة من التردد
            $table->date('next_scheduled_date')->nullable();
            $table->date('last_executed_date')->nullable();

            // التفاصيل
            $table->jsonb('checklist')->default('[]');   // قائمة فحص تفصيلية
            $table->text('instructions')->nullable();     // تعليمات التنفيذ

            // التعيين
            $table->foreignUuid('contractor_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            // التكلفة
            $table->decimal('estimated_cost', 12, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('auto_create_wo')->default(true); // إنشاء أمر عمل تلقائياً
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'is_active']);
            $table->index('next_scheduled_date');
        });

        // ─── سجلات الصيانة الوقائية ──────────────────
        Schema::create('pm_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pm_plan_id')->constrained('pm_plans')->cascadeOnDelete();
            $table->foreignUuid('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();

            $table->date('scheduled_date');
            $table->date('executed_date')->nullable();
            $table->string('executed_by_type')->nullable(); // staff, contractor
            $table->uuid('executed_by_id')->nullable();

            $table->jsonb('checklist_results')->default('{}');
            $table->string('result')->nullable();         // pass, fail, partial
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->jsonb('photos')->default('[]');
            $table->decimal('cost', 12, 2)->nullable();

            $table->timestamps();

            $table->index('pm_plan_id');
            $table->index('scheduled_date');
        });

        // ─── الفحوصات والمعاينات ─────────────────────
        Schema::create('inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignUuid('inspector_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type');                      // onboarding, periodic, move_in, move_out, safety, complaint, pre_lease
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled

            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();

            // النتائج
            $table->jsonb('checklist')->default('[]');   // قائمة فحص حسب النوع
            $table->jsonb('results')->default('{}');     // نتائج الفحص
            $table->decimal('overall_rating', 3, 1)->nullable(); // 1.0 - 5.0
            $table->jsonb('photos')->default('[]');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();

            // أوامر عمل ناتجة
            $table->jsonb('generated_work_orders')->default('[]'); // UUIDs of created WOs

            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'type']);
            $table->index('status');
            $table->index('scheduled_date');
        });

        // Add foreign key for pm_plan_id on work_orders
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreign('pm_plan_id')->references('id')->on('pm_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['pm_plan_id']);
        });
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('pm_logs');
        Schema::dropIfExists('pm_plans');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('contractors');
    }
};
