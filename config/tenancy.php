<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | PM-OS Tenant Model
    |--------------------------------------------------------------------------
    */
    'tenant_model' => App\Core\MultiTenancy\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    | النطاقات المركزية للـ SaaS (لوحة الإدارة العامة)
    */
    'central_domains' => explode(',', env('TENANCY_CENTRAL_DOMAINS', 'pmos.test')),

    /*
    |--------------------------------------------------------------------------
    | Identification
    |--------------------------------------------------------------------------
    | تحديد الـ Tenant عبر النطاق الفرعي: company.pmos.sa
    */
    'identification' => [
        'resolvers' => [
            Stancl\Tenancy\Resolvers\DomainTenantResolver::class,
        ],
        'early_identification' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    | كل شركة إدارة أملاك = قاعدة بيانات مستقلة
    */
    'database' => [
        // Central connection used for SaaS data and as the template for
        // dynamically-created tenant connections (stancl/tenancy).
        'central_connection' => env('TENANCY_CENTRAL_CONNECTION', 'central'),

        'prefix' => env('TENANCY_DB_PREFIX', 'pm_tenant_'),
        'suffix' => '',

        'managers' => [
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem
    |--------------------------------------------------------------------------
    | Read by Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper.
    */
    'filesystem' => [
        'suffix_base' => 'tenant',

        // Tenant-scope the storage path so each company's files are isolated.
        'suffix_storage_path' => true,

        // IMPORTANT: do NOT rewrite the asset() helper (and therefore the
        // @vite directive) to the tenant asset route (/tenancy/assets/...).
        // The compiled frontend lives in public/build and is served directly
        // by the web server; with this left at the stancl default (true), every
        // Vite asset URL on a tenant domain becomes /tenancy/assets/build/...
        // and 404s, leaving a blank page.
        'asset_helper_tenancy' => false,

        // No per-disk root suffixing configured yet. Keep as an empty array so
        // the bootstrapper has an iterable (a null here warns on foreach).
        'disks' => [],
        'root_override' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    | الخدمات التي يتم تهيئتها لكل Tenant
    */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Migration Path
    |--------------------------------------------------------------------------
    */
    'migration_parameters' => [
        '--path' => [
            database_path('migrations/tenant'),
        ],
        '--realpath' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder
    |--------------------------------------------------------------------------
    */
    'seeder_parameters' => [
        '--class' => Database\Seeders\TenantDatabaseSeeder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
    ],
];
