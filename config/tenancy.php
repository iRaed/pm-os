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
        'prefix' => env('TENANCY_DB_PREFIX', 'pm_tenant_'),
        'suffix' => '',

        'managers' => [
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
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
