<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\MultiTenancy\Models\Plan;
use App\Core\MultiTenancy\Models\Tenant;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Throwable;

/**
 * إنشاء شركة (Tenant) جديدة بقاعدة بيانات ونطاق مستقلين
 *
 * Usage:
 *   php artisan tenant:create <identifier> <name> <domain> [--plan=] [--status=trial] [--seed]
 *
 * Example:
 *   php artisan tenant:create demo "شركة الواحة" demo.pmos.test --plan=professional --seed
 */
class CreateTenant extends Command
{
    protected $signature = 'tenant:create
        {identifier : Readable identifier / subdomain slug, e.g. demo}
        {name : Company name (Arabic)}
        {domain : Primary domain, e.g. demo.pmos.test}
        {--plan= : Plan slug (starter|professional|enterprise) or UUID}
        {--status=trial : trial|active|suspended|cancelled}
        {--seed : Seed the tenant database with demo data after migration}';

    protected $description = 'Create a new tenant (company) with its own database and domain';

    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $name = $this->argument('name');
        $domain = strtolower($this->argument('domain'));
        $status = $this->option('status');

        // Domain uniqueness
        if (Tenant::query()->getConnection()->table('domains')->where('domain', $domain)->exists()) {
            $this->error("Domain [{$domain}] is already registered.");

            return self::FAILURE;
        }

        // Resolve plan (optional)
        $plan = null;
        if ($planRef = $this->option('plan')) {
            $plan = Plan::where('slug', $planRef)
                ->when(Str::isUuid($planRef), fn ($q) => $q->orWhere('id', $planRef))
                ->first();
            if (! $plan) {
                $this->error("Plan [{$planRef}] not found. Seed plans first: php artisan db:seed --class=PlanSeeder");

                return self::FAILURE;
            }
        }

        $this->info("Creating tenant [{$identifier}] — {$name} …");

        try {
            // Tenant row (id is an auto-generated UUID; HasUuids). Creating the
            // tenant fires TenantCreated, which (via TenancyServiceProvider)
            // creates and migrates the tenant database synchronously.
            $tenant = Tenant::create([
                'name' => $name,
                'plan_id' => $plan?->id,
                'max_units' => $plan?->max_units ?? 50,
                'max_users' => $plan?->max_users ?? 5,
                'status' => $status,
                'trial_ends_at' => $status === Tenant::STATUS_TRIAL ? now()->addDays(14) : null,
                'settings' => ['identifier' => $identifier],
            ]);

            $tenant->domains()->create(['domain' => $domain]);
        } catch (Throwable $e) {
            $this->error('Failed to create tenant: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("✓ Tenant created");
        $this->table(['Field', 'Value'], [
            ['ID (UUID)', $tenant->id],
            ['Identifier', $identifier],
            ['Name', $tenant->name],
            ['Domain', $domain],
            ['Database', $tenant->database()->getName()],
            ['Plan', $plan?->slug ?? '—'],
            ['Status', $tenant->status],
        ]);

        // Optional: seed tenant demo data inside the tenant context
        if ($this->option('seed')) {
            $this->info('Seeding tenant database …');
            $tenant->run(function () {
                Artisan::call('db:seed', [
                    '--class' => TenantDatabaseSeeder::class,
                    '--force' => true,
                ], $this->output);
            });
            $this->info('✓ Tenant database seeded');
        }

        return self::SUCCESS;
    }
}
