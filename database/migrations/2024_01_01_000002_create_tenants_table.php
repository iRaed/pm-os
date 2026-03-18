<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول الشركات المستأجرة — Central DB
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // بيانات الشركة
            $table->string('name');                    // اسم الشركة بالعربي
            $table->string('name_en')->nullable();     // اسم الشركة بالإنجليزي
            $table->string('commercial_reg')->nullable(); // رقم السجل التجاري
            $table->string('vat_number')->nullable();     // الرقم الضريبي (زاتكا)
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();

            // الاشتراك
            $table->foreignUuid('plan_id')
                ->nullable()
                ->constrained('plans')
                ->nullOnDelete();
            $table->integer('max_units')->default(50);
            $table->integer('max_users')->default(5);

            // الحالة
            $table->string('status')->default('trial');  // trial, active, suspended, cancelled
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Stancl data column
            $table->jsonb('data')->nullable();

            // إعدادات مخصصة
            $table->jsonb('settings')->default('{}');

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('plan_id');
        });

        // جدول النطاقات الفرعية
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->unique();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
        Schema::dropIfExists('tenants');
    }
};
