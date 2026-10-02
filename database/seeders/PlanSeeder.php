<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\MultiTenancy\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * خطط الاشتراك — Central DB
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'الأساسية',
                'name_en' => 'Starter',
                'slug' => Plan::PLAN_STARTER,
                'max_units' => 50,
                'max_users' => 5,
                'monthly_price' => 499.00,
                'annual_price' => 4990.00,
                'features' => [
                    'property_management',
                    'leasing',
                    'collection',
                    'basic_reports',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'الاحترافية',
                'name_en' => 'Professional',
                'slug' => Plan::PLAN_PROFESSIONAL,
                'max_units' => 500,
                'max_users' => 25,
                'monthly_price' => 1499.00,
                'annual_price' => 14990.00,
                'features' => [
                    'property_management',
                    'leasing',
                    'collection',
                    'maintenance',
                    'finance',
                    'owner_statements',
                    'advanced_reports',
                    'api_access',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'المؤسسية',
                'name_en' => 'Enterprise',
                'slug' => Plan::PLAN_ENTERPRISE,
                'max_units' => 999999,
                'max_users' => 1000,
                'monthly_price' => 4999.00,
                'annual_price' => 49990.00,
                'features' => [
                    'property_management',
                    'leasing',
                    'collection',
                    'maintenance',
                    'finance',
                    'owner_statements',
                    'advanced_reports',
                    'api_access',
                    'ai_engine',
                    'white_label',
                    'priority_support',
                    'custom_integrations',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
