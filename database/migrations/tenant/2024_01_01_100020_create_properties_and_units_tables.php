<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * جداول العقارات والوحدات — الجداول المحورية للنظام
     */
    public function up(): void
    {
        // ─── العقارات ────────────────────────────────
        Schema::create('properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignUuid('manager_id')->nullable()->constrained('users')->nullOnDelete();

            // التعريف
            $table->string('name');                    // اسم العقار
            $table->string('name_en')->nullable();
            $table->string('code')->unique();          // كود فريد: PROP-001

            // التصنيف
            $table->string('type');                    // residential_compound, commercial_building, tower, villa, mixed_use, land, warehouse, mall, office_building
            $table->string('sub_type')->nullable();    // تصنيف فرعي مخصص
            $table->string('status')->default('onboarding'); // onboarding, active, suspended, archived

            // الموقع
            $table->string('address_line');
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('additional_number')->nullable(); // الرقم الإضافي (عنوان وطني)
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // المواصفات
            $table->integer('total_units')->default(0);
            $table->decimal('total_area_sqm', 12, 2)->nullable();
            $table->decimal('land_area_sqm', 12, 2)->nullable();
            $table->integer('year_built')->nullable();
            $table->integer('floors_count')->nullable();
            $table->integer('parking_spots')->nullable();

            // المرافق والميزات
            $table->jsonb('amenities')->default('[]');  // مسبح، صالة، حديقة، نادي...

            // التواريخ التشغيلية
            $table->date('onboarding_date')->nullable();
            $table->date('operation_start_date')->nullable();
            $table->date('deed_date')->nullable();       // تاريخ الصك
            $table->string('deed_number')->nullable();   // رقم الصك

            // التقييم
            $table->string('risk_level')->default('low'); // low, medium, high, critical
            $table->decimal('market_value', 15, 2)->nullable();
            $table->decimal('insurance_value', 15, 2)->nullable();

            // بيانات مرنة
            $table->jsonb('metadata')->default('{}');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('owner_id');
            $table->index('status');
            $table->index('type');
            $table->index('city');
            $table->index(['city', 'district']);
        });

        // PostGIS Spatial Index (if PostGIS is available)
        try {
            DB::statement('CREATE INDEX idx_properties_location ON properties USING GIST(ST_SetSRID(ST_MakePoint(lng::double precision, lat::double precision), 4326)) WHERE lat IS NOT NULL AND lng IS NOT NULL');
        } catch (\Exception $e) {
            // PostGIS not available — skip spatial index
        }

        // ─── الوحدات ─────────────────────────────────
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();

            // التعريف
            $table->string('unit_number');             // رقم الوحدة
            $table->string('unit_code')->nullable();   // كود فريد: PROP-001-U-101
            $table->integer('floor')->nullable();       // الطابق

            // التصنيف
            $table->string('type');                    // apartment, office, shop, studio, villa, warehouse, parking, storage, other
            $table->string('status')->default('vacant'); // vacant, occupied, under_maintenance, reserved, not_available

            // المواصفات
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->integer('rooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('living_rooms')->nullable();
            $table->boolean('has_kitchen')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->string('view_direction')->nullable(); // north, south, east, west, street, garden

            // التسعير
            $table->decimal('base_rent', 12, 2)->nullable();       // الإيجار الأساسي
            $table->decimal('market_rent', 12, 2)->nullable();     // إيجار السوق المقدّر
            $table->decimal('last_rent', 12, 2)->nullable();       // آخر إيجار مُحصّل

            // العدادات
            $table->jsonb('meter_numbers')->default('{}'); // {electricity: "xxx", water: "xxx", gas: "xxx"}

            // المرافق
            $table->jsonb('features')->default('[]');   // مكيفات، مطبخ مجهز، خزائن...
            $table->jsonb('appliances')->default('[]'); // أجهزة مُسلّمة مع الوحدة

            // الفحص
            $table->date('last_inspection_date')->nullable();
            $table->string('condition_rating')->nullable(); // excellent, good, fair, poor

            // بيانات مرنة
            $table->jsonb('metadata')->default('{}');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['property_id', 'status']);
            $table->index('type');
            $table->unique(['property_id', 'unit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
        Schema::dropIfExists('properties');
    }
};
