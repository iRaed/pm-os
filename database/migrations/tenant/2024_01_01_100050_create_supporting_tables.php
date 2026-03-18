<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الجداول المساندة: المستندات، التواصل، المحاسبة، التنبيهات، المخاطر، جمعيات الملاك
     */
    public function up(): void
    {
        // ─── المستندات (Polymorphic) ─────────────────
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('documentable');          // property, unit, owner, resident, lease, contractor, inspection

            $table->string('category');                  // deed, license, contract, identity, inspection_report, insurance, financial, legal, photo, floor_plan, other
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');                 // S3 path
            $table->string('file_name');                 // Original filename
            $table->integer('file_size')->nullable();    // bytes
            $table->string('mime_type')->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('expiry_date')->nullable();     // للوثائق ذات صلاحية
            $table->boolean('is_verified')->default(false);
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->boolean('is_encrypted')->default(false);
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id']);
            $table->index('category');
            $table->index('expiry_date');
        });

        // ─── المراسلات والتواصل (Polymorphic) ────────
        Schema::create('communications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('communicatable'); // resident, owner, contractor

            $table->string('channel');                   // sms, email, whatsapp, push, in_app, call, letter
            $table->string('direction')->default('outbound'); // outbound, inbound
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('template_id')->nullable();   // قالب الرسالة

            // المُرسل
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->nullOnDelete();

            // المستلم
            $table->string('recipient_type')->nullable(); // resident, owner, contractor, user
            $table->uuid('recipient_id')->nullable();
            $table->string('recipient_contact')->nullable(); // phone/email used

            // الحالة
            $table->string('status')->default('queued'); // queued, sent, delivered, read, failed, bounced
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failure_reason')->nullable();

            // المرجع الخارجي
            $table->string('external_id')->nullable();   // Message ID from provider

            $table->jsonb('metadata')->default('{}');
            $table->timestamps();

            $table->index(['communicatable_type', 'communicatable_id']);
            $table->index('channel');
            $table->index('status');
            $table->index('created_at');
        });

        // ─── دفتر الأستاذ (المعاملات المالية) ────────
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignUuid('owner_id')->nullable()->constrained('owners')->nullOnDelete();

            // التصنيف المحاسبي
            $table->string('account_type');              // revenue, expense, asset, liability
            $table->string('category');                  // rent, deposit, maintenance, management_fee, insurance, utility, tax, capital_expense, service_charge, penalty, refund, other
            $table->string('sub_category')->nullable();

            // المبلغ
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->string('currency')->default('SAR');

            $table->text('description');

            // المرجع (Polymorphic)
            $table->nullableUuidMorphs('reference');     // invoice, payment, work_order, etc.

            $table->date('transaction_date');
            $table->string('period')->nullable();        // 2024-01, 2024-Q1 (فترة محاسبية)
            $table->boolean('is_reconciled')->default(false);

            $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->jsonb('metadata')->default('{}');
            $table->timestamps();

            $table->index(['property_id', 'transaction_date']);
            $table->index(['account_type', 'category']);
            $table->index('transaction_date');
            $table->index('period');
        });

        // ─── التنبيهات والتذكيرات ────────────────────
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('alertable');     // lease, invoice, inspection, pm_plan, etc.

            $table->string('type');                      // lease_expiry, payment_due, payment_overdue, maintenance_due, insurance_expiry, license_expiry, inspection_due, sla_breach, budget_exceeded, occupancy_low, custom
            $table->string('severity')->default('info'); // info, warning, critical
            $table->string('title');
            $table->text('message')->nullable();

            $table->timestamp('trigger_date');           // تاريخ التنبيه
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // التعيين
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_to_role')->nullable(); // يمكن التعيين لدور كامل

            $table->boolean('is_auto_generated')->default(true);
            $table->boolean('is_dismissed')->default(false);

            $table->jsonb('actions')->default('[]');     // إجراءات مقترحة
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();

            $table->index(['type', 'is_read']);
            $table->index(['assigned_to', 'is_read']);
            $table->index('trigger_date');
            $table->index('severity');
        });

        // ─── سجل المخاطر ─────────────────────────────
        Schema::create('risks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('riskable');      // property, unit, contractor, etc.

            $table->string('category');                  // operational, financial, legal, safety, tenant, vendor, market, compliance
            $table->string('title');
            $table->text('description')->nullable();

            // تقييم المخاطر
            $table->string('likelihood');                // rare, unlikely, possible, likely, certain
            $table->string('impact');                    // negligible, minor, moderate, major, catastrophic
            $table->integer('risk_score')->nullable();   // calculated: likelihood x impact (1-25)

            // الاستجابة
            $table->text('mitigation_plan')->nullable();
            $table->text('contingency_plan')->nullable();
            $table->string('response_strategy')->nullable(); // avoid, mitigate, transfer, accept

            // الحالة
            $table->string('status')->default('identified'); // identified, assessing, mitigating, accepted, resolved, closed
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_date')->nullable();
            $table->date('resolved_date')->nullable();

            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'status']);
            $table->index('risk_score');
        });

        // ─── جمعيات الملاك ────────────────────────────
        Schema::create('hoa_associations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('annual_budget', 15, 2)->nullable();
            $table->decimal('reserve_fund_balance', 15, 2)->default(0);
            $table->foreignUuid('bylaws_document_id')->nullable();
            $table->string('status')->default('active'); // active, inactive, dissolved
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hoa_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hoa_id')->constrained('hoa_associations')->cascadeOnDelete();
            $table->foreignUuid('owner_id')->constrained('owners')->cascadeOnDelete();

            $table->jsonb('unit_ids')->default('[]');    // الوحدات المملوكة
            $table->decimal('ownership_share_pct', 7, 4); // نسبة الملكية
            $table->decimal('voting_weight', 7, 4)->nullable();
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->string('status')->default('active'); // active, suspended, withdrawn
            $table->timestamps();

            $table->unique(['hoa_id', 'owner_id']);
        });

        Schema::create('hoa_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hoa_id')->constrained('hoa_associations')->cascadeOnDelete();

            $table->string('type');                      // annual, extraordinary
            $table->string('title');
            $table->timestamp('scheduled_date');
            $table->string('location')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled

            $table->jsonb('agenda')->default('[]');       // بنود جدول الأعمال
            $table->text('minutes')->nullable();          // محضر الاجتماع
            $table->jsonb('decisions')->default('[]');     // القرارات
            $table->jsonb('votes')->default('[]');         // التصويتات
            $table->jsonb('attendees')->default('[]');     // الحاضرون
            $table->integer('quorum_required')->nullable();
            $table->integer('quorum_achieved')->nullable();

            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hoa_id', 'status']);
        });

        // ─── قوالب الإشعارات ──────────────────────────
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();            // payment_reminder, lease_expiry, etc.
            $table->string('name');                      // اسم القالب
            $table->string('channel');                   // sms, email, whatsapp, push
            $table->string('subject')->nullable();       // عنوان (للبريد)
            $table->text('body_ar');                     // النص بالعربي مع placeholders
            $table->text('body_en')->nullable();         // النص بالإنجليزي
            $table->jsonb('variables')->default('[]');   // المتغيرات المتاحة
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─── إعدادات الشركة ───────────────────────────
        Schema::create('company_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('group');                     // general, financial, notifications, branding, integrations
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string');   // string, integer, boolean, json, encrypted
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('hoa_meetings');
        Schema::dropIfExists('hoa_members');
        Schema::dropIfExists('hoa_associations');
        Schema::dropIfExists('risks');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('communications');
        Schema::dropIfExists('documents');
    }
};
