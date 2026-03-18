<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول الملاك — Tenant DB
     */
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                         // اسم المالك بالعربي
            $table->string('name_en')->nullable();
            $table->string('national_id_type')->default('saudi_id'); // saudi_id, iqama, company_cr
            $table->string('national_id');
            $table->string('phone');
            $table->string('phone_2')->nullable();
            $table->string('email')->nullable();
            $table->string('iban')->nullable();              // رقم الآيبان
            $table->string('bank_name')->nullable();
            $table->decimal('management_fee_pct', 5, 2)->default(5.00); // نسبة الإدارة %
            $table->date('contract_start')->nullable();      // بداية عقد الإدارة
            $table->date('contract_end')->nullable();        // نهاية عقد الإدارة
            $table->string('tax_registration_no')->nullable(); // رقم التسجيل الضريبي
            $table->jsonb('address')->default('{}');          // العنوان التفصيلي
            $table->string('status')->default('active');      // active, inactive, suspended
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->default('{}');         // بيانات إضافية مرنة
            $table->timestamps();
            $table->softDeletes();

            $table->index('national_id');
            $table->index('status');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
