<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Foundation\Models\Owner;
use Modules\Foundation\Models\Property;
use Modules\Foundation\Models\Unit;
use Modules\Foundation\Models\User;
use Modules\TenantManagement\Models\Resident;
use Modules\Leasing\Models\Lease;
use Modules\Maintenance\Models\Contractor;

/**
 * بيانات تجريبية لكل Tenant جديد
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. الأدوار والصلاحيات
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. المستخدم المدير
        $admin = User::create([
            'name' => 'مدير النظام',
            'name_en' => 'System Admin',
            'email' => 'admin@pmos.test',
            'phone' => '0500000001',
            'password' => Hash::make('password'),
            'job_title' => 'مدير عام',
            'department' => 'الإدارة',
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $admin->assignRole('super_admin');

        // 3. مستخدمون تجريبيون
        $manager = User::create([
            'name' => 'أحمد المدير',
            'email' => 'manager@pmos.test',
            'phone' => '0500000002',
            'password' => Hash::make('password'),
            'job_title' => 'مدير عقارات',
            'department' => 'إدارة الأملاك',
            'is_active' => true,
        ]);
        $manager->assignRole('property_manager');

        $accountant = User::create([
            'name' => 'سارة المحاسبة',
            'email' => 'accountant@pmos.test',
            'phone' => '0500000003',
            'password' => Hash::make('password'),
            'job_title' => 'محاسبة',
            'department' => 'المالية',
            'is_active' => true,
        ]);
        $accountant->assignRole('accountant');

        $technician = User::create([
            'name' => 'خالد الفني',
            'email' => 'tech@pmos.test',
            'phone' => '0500000004',
            'password' => Hash::make('password'),
            'job_title' => 'فني صيانة',
            'department' => 'الصيانة',
            'is_active' => true,
        ]);
        $technician->assignRole('technician');

        // 4. مالك تجريبي
        $owner = Owner::create([
            'name' => 'عبدالله بن محمد الشهري',
            'national_id_type' => 'saudi_id',
            'national_id' => '1088888888',
            'phone' => '0555555501',
            'email' => 'owner@example.com',
            'iban' => 'SA0380000000608010167519',
            'bank_name' => 'بنك الراجحي',
            'management_fee_pct' => 5.00,
            'contract_start' => now()->startOfYear(),
            'contract_end' => now()->addYear()->endOfYear(),
            'status' => 'active',
            'address' => [
                'city' => 'الرياض',
                'district' => 'العليا',
                'street' => 'طريق الملك فهد',
            ],
        ]);

        // 5. عقار تجريبي — مجمع سكني
        $property = Property::create([
            'owner_id' => $owner->id,
            'manager_id' => $manager->id,
            'name' => 'مجمع الواحة السكني',
            'name_en' => 'Al Waha Residential Compound',
            'code' => 'PROP-0001',
            'type' => Property::TYPE_RESIDENTIAL_COMPOUND,
            'status' => Property::STATUS_ACTIVE,
            'address_line' => 'حي النرجس، شارع الأمير سلطان',
            'city' => 'الرياض',
            'district' => 'النرجس',
            'postal_code' => '13326',
            'lat' => 24.8217,
            'lng' => 46.6274,
            'total_units' => 24,
            'total_area_sqm' => 3500,
            'year_built' => 2020,
            'floors_count' => 4,
            'parking_spots' => 30,
            'amenities' => ['مسبح', 'صالة رياضية', 'حديقة', 'مواقف مغطاة', 'حارس أمن'],
            'onboarding_date' => now()->subMonths(6),
            'operation_start_date' => now()->subMonths(5),
            'deed_number' => '310120001234',
            'risk_level' => Property::RISK_LOW,
        ]);

        // 6. الوحدات
        $unitTypes = ['apartment', 'apartment', 'apartment', 'studio'];
        $unitNumber = 100;

        for ($floor = 1; $floor <= 4; $floor++) {
            for ($u = 1; $u <= 6; $u++) {
                $unitNumber++;
                $type = $u <= 4 ? 'apartment' : ($u === 5 ? 'studio' : 'apartment');
                $rooms = $type === 'studio' ? 1 : rand(2, 3);
                $isOccupied = rand(0, 100) < 85; // 85% إشغال

                Unit::create([
                    'property_id' => $property->id,
                    'unit_number' => (string) $unitNumber,
                    'floor' => $floor,
                    'type' => $type,
                    'status' => $isOccupied ? Unit::STATUS_OCCUPIED : Unit::STATUS_VACANT,
                    'area_sqm' => $type === 'studio' ? 45 : rand(80, 140),
                    'rooms' => $rooms,
                    'bathrooms' => $rooms === 1 ? 1 : 2,
                    'living_rooms' => $type === 'studio' ? 0 : 1,
                    'has_kitchen' => true,
                    'has_balcony' => $floor >= 3,
                    'base_rent' => $type === 'studio' ? 18000 : ($rooms === 2 ? 28000 : 35000),
                    'features' => ['مكيفات سبليت', 'مطبخ مجهز', 'خزائن حائط'],
                    'meter_numbers' => [
                        'electricity' => 'E-' . $unitNumber . '-' . rand(1000, 9999),
                        'water' => 'W-' . $unitNumber . '-' . rand(1000, 9999),
                    ],
                    'condition_rating' => 'good',
                ]);
            }
        }

        // 7. مستأجرون تجريبيون
        $residentNames = [
            'محمد العتيبي', 'فهد الدوسري', 'سلطان الشمري', 'نواف القحطاني',
            'يوسف الغامدي', 'عبدالرحمن الحربي', 'تركي المطيري', 'سعود البقمي',
            'بندر الزهراني', 'ماجد العنزي', 'طلال السبيعي', 'عادل الجهني',
            'هاني الثقفي', 'وليد الرشيدي', 'مشعل السهلي', 'صالح الشهراني',
            'أنس العمري', 'خالد المالكي', 'عمر الحازمي', 'زياد البلوي',
        ];

        $occupiedUnits = Unit::where('property_id', $property->id)
            ->where('status', Unit::STATUS_OCCUPIED)
            ->get();

        foreach ($occupiedUnits as $index => $unit) {
            if ($index >= count($residentNames)) break;

            $resident = Resident::create([
                'name' => $residentNames[$index],
                'national_id_type' => 'saudi_id',
                'national_id' => '10' . str_pad((string) ($index + 10000001), 8, '0', STR_PAD_LEFT),
                'phone' => '05' . rand(10000000, 99999999),
                'email' => 'resident' . ($index + 1) . '@example.com',
                'nationality' => 'سعودي',
                'status' => Resident::STATUS_ACTIVE,
                'onboarding_completed' => true,
                'source' => ['walk_in', 'online', 'referral', 'aqar'][rand(0, 3)],
            ]);

            // عقد إيجار
            $startDate = now()->subMonths(rand(1, 11));
            Lease::create([
                'unit_id' => $unit->id,
                'resident_id' => $resident->id,
                'created_by' => $manager->id,
                'lease_number' => Lease::generateLeaseNumber(),
                'type' => Lease::TYPE_NEW,
                'status' => Lease::STATUS_ACTIVE,
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addYear(),
                'duration_months' => 12,
                'rent_amount' => $unit->base_rent,
                'rent_frequency' => Lease::FREQ_ANNUAL,
                'deposit_amount' => $unit->base_rent * 0.1,
                'payment_method' => ['bank_transfer', 'sadad', 'mada'][rand(0, 2)],
                'auto_renew' => (bool) rand(0, 1),
            ]);
        }

        // 8. مقاولون تجريبيون
        $contractors = [
            ['name' => 'مؤسسة الرواد للسباكة', 'specializations' => ['plumbing'], 'phone' => '0500000010'],
            ['name' => 'شركة النور للكهرباء', 'specializations' => ['electrical'], 'phone' => '0500000011'],
            ['name' => 'مؤسسة البرد للتكييف', 'specializations' => ['hvac'], 'phone' => '0500000012'],
            ['name' => 'شركة الإتقان للصيانة', 'specializations' => ['general', 'painting', 'structural'], 'phone' => '0500000013'],
            ['name' => 'مؤسسة الحماية لمكافحة الحشرات', 'specializations' => ['pest_control'], 'phone' => '0500000014'],
        ];

        foreach ($contractors as $c) {
            Contractor::create([
                'name' => $c['name'],
                'phone' => $c['phone'],
                'specializations' => $c['specializations'],
                'contract_type' => 'framework',
                'status' => 'active',
                'rating' => round(rand(35, 50) / 10, 1),
            ]);
        }

        // 9. إعدادات الشركة الأساسية
        $settings = [
            ['group' => 'general', 'key' => 'company_name', 'value' => 'شركة الواحة لإدارة الأملاك', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_name_en', 'value' => 'Al Waha Property Management Co.', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_phone', 'value' => '0112345678', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_email', 'value' => 'info@alwaha-pm.sa', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_address', 'value' => 'الرياض - حي العليا - طريق الملك فهد', 'type' => 'string'],
            ['group' => 'financial', 'key' => 'vat_rate', 'value' => '15', 'type' => 'integer'],
            ['group' => 'financial', 'key' => 'currency', 'value' => 'SAR', 'type' => 'string'],
            ['group' => 'financial', 'key' => 'invoice_prefix', 'value' => 'INV', 'type' => 'string'],
            ['group' => 'financial', 'key' => 'payment_grace_days', 'value' => '3', 'type' => 'integer'],
            ['group' => 'notifications', 'key' => 'lease_expiry_alert_days', 'value' => '90,60,30', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'payment_reminder_days', 'value' => '-3,0,3,7,14,30', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'sms_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notifications', 'key' => 'whatsapp_enabled', 'value' => '1', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            \DB::table('company_settings')->insert(array_merge($setting, [
                'id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
