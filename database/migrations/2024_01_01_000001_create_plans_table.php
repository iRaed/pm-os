<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول خطط الاشتراك — Central DB
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');               // اسم الخطة بالعربي
            $table->string('name_en');             // اسم الخطة بالإنجليزي
            $table->string('slug')->unique();      // المعرّف الفريد
            $table->integer('max_units');           // الحد الأقصى للوحدات
            $table->integer('max_users');           // الحد الأقصى للمستخدمين
            $table->decimal('monthly_price', 10, 2); // السعر الشهري (ريال)
            $table->decimal('annual_price', 10, 2);  // السعر السنوي (ريال)
            $table->jsonb('features')->default('[]'); // الميزات المتاحة
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
