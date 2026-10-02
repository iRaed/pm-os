<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Central (SaaS) database seeder.
 *
 * Tenant-level demo data is seeded per-tenant via TenantDatabaseSeeder
 * (run inside the tenant context), not from here.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
        ]);
    }
}
