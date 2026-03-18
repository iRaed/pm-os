<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول المستخدمين — Tenant DB
     * موظفو شركة إدارة الأملاك
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable();
            $table->string('national_id_type')->default('saudi_id'); // saudi_id, iqama, passport
            $table->string('employee_number')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('locale')->default('ar');   // ar, en
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->jsonb('settings')->default('{}');   // تفضيلات شخصية
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('phone');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
