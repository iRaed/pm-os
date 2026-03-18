<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جداول المستأجرين والعقود والفواتير والمدفوعات
     */
    public function up(): void
    {
        // ─── المستأجرون (السكان) ─────────────────────
        Schema::create('residents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // البيانات الشخصية
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('national_id_type')->default('saudi_id'); // saudi_id, iqama, passport, company_cr
            $table->string('national_id');
            $table->string('phone');
            $table->string('phone_2')->nullable();
            $table->string('email')->nullable();
            $table->string('nationality')->nullable();

            // بيانات تجارية (للمستأجر التجاري)
            $table->string('company_name')->nullable();
            $table->string('commercial_reg')->nullable();
            $table->string('tax_number')->nullable();

            // جهة اتصال الطوارئ
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // التقييم والحالة
            $table->string('status')->default('prospect'); // prospect, active, former, blacklisted
            $table->integer('credit_score')->nullable();    // 1-100
            $table->decimal('rating', 3, 1)->nullable();    // 1.0-5.0
            $table->boolean('onboarding_completed')->default(false);

            // المصدر
            $table->string('source')->nullable(); // walk_in, online, referral, agent, aqar, haraj

            $table->text('notes')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index('national_id');
            $table->index('status');
            $table->index('phone');
        });

        // ─── العقود (عقود الإيجار) ───────────────────
        Schema::create('leases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignUuid('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            // التعريف
            $table->string('lease_number')->unique();   // رقم العقد: LSE-2024-001
            $table->string('ejar_contract_id')->nullable(); // رقم عقد إيجار (وزارة الإسكان)

            // النوع والحالة
            $table->string('type')->default('new');     // new, renewal, sublease
            $table->string('status')->default('draft'); // draft, pending_approval, active, expiring, expired, terminated, cancelled

            // المدة
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_months')->nullable(); // computed helper

            // المبالغ
            $table->decimal('rent_amount', 12, 2);       // مبلغ الإيجار
            $table->string('rent_frequency')->default('annual'); // monthly, quarterly, semi_annual, annual
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0); // عمولة السعي
            $table->string('currency')->default('SAR');

            // طريقة الدفع
            $table->string('payment_method')->default('bank_transfer'); // cash, bank_transfer, sadad, mada, cheque

            // شروط خاصة
            $table->jsonb('terms')->default('{}');       // شروط إضافية
            $table->jsonb('included_utilities')->default('[]'); // خدمات مشمولة: كهرباء، مياه

            // التجديد التلقائي
            $table->boolean('auto_renew')->default(false);
            $table->decimal('renewal_increase_pct', 5, 2)->nullable(); // نسبة الزيادة عند التجديد

            // التوقيع
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by_resident')->nullable();
            $table->string('signed_by_manager')->nullable();
            $table->string('document_path')->nullable();  // رابط ملف العقد الموقّع

            // العقد السابق (للتجديدات)
            $table->foreignUuid('previous_lease_id')->nullable()->constrained('leases')->nullOnDelete();

            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_id', 'status']);
            $table->index(['resident_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        // ─── الفواتير ────────────────────────────────
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->nullable()->constrained('leases')->nullOnDelete();
            $table->foreignUuid('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();

            // التعريف
            $table->string('invoice_number')->unique();  // INV-2024-00001

            // النوع
            $table->string('type');                      // rent, deposit, service_charge, penalty, maintenance, parking, other

            // المبالغ
            $table->decimal('subtotal', 12, 2);          // المبلغ قبل الضريبة
            $table->decimal('vat_rate', 5, 2)->default(15.00); // نسبة ضريبة القيمة المضافة
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);      // المبلغ الإجمالي
            $table->decimal('paid_amount', 12, 2)->default(0);   // المبلغ المدفوع
            $table->decimal('balance', 12, 2);           // الرصيد المتبقي
            $table->string('currency')->default('SAR');

            // التواريخ
            $table->date('issued_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();

            // الحالة
            $table->string('status')->default('draft');  // draft, issued, sent, partially_paid, paid, overdue, cancelled, written_off

            // سداد
            $table->string('sadad_number')->nullable();
            $table->string('sadad_status')->nullable();

            // زاتكا (الفوترة الإلكترونية)
            $table->string('zatca_invoice_hash')->nullable();
            $table->string('zatca_qr_code')->nullable();
            $table->boolean('zatca_reported')->default(false);

            // الفترة
            $table->date('period_start')->nullable();    // بداية الفترة المفوترة
            $table->date('period_end')->nullable();      // نهاية الفترة المفوترة

            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('line_items')->default('[]');   // تفاصيل البنود
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date']);
            $table->index(['resident_id', 'status']);
            $table->index(['property_id', 'status']);
            $table->index('due_date');
        });

        // ─── المدفوعات ───────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUuid('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->foreignUuid('collected_by')->nullable()->constrained('users')->nullOnDelete();

            // المبلغ
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('SAR');

            // التفاصيل
            $table->date('payment_date');
            $table->string('method');                    // cash, bank_transfer, sadad, mada, cheque, online
            $table->string('reference_number')->nullable(); // رقم مرجعي (حوالة، شيك...)
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();

            // الحالة
            $table->string('status')->default('pending'); // pending, confirmed, bounced, refunded, cancelled

            // الإيصال
            $table->string('receipt_number')->nullable();
            $table->string('receipt_path')->nullable();   // رابط الإيصال

            $table->text('notes')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'status']);
            $table->index('payment_date');
            $table->index('method');
        });

        // ─── جداول الدفعات المجدولة ──────────────────
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained('leases')->cascadeOnDelete();
            $table->foreignUuid('resident_id')->constrained('residents')->cascadeOnDelete();

            $table->integer('installment_number');       // رقم الدفعة
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, invoiced, paid, overdue
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->timestamps();

            $table->index(['lease_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('leases');
        Schema::dropIfExists('residents');
    }
};
