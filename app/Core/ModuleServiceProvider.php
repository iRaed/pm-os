<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Support\ServiceProvider;

/**
 * Base Module Service Provider
 * كل Module يمتد من هذا الـ Provider لتوحيد التسجيل
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * اسم الموديول (يُستخدم في المسارات والـ Config)
     */
    abstract protected function moduleName(): string;

    /**
     * مسار الموديول
     */
    protected function modulePath(): string
    {
        return app_path("Modules/{$this->moduleName()}");
    }

    /**
     * تسجيل الخدمات
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerRepositories();
    }

    /**
     * تهيئة الخدمات
     */
    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootMigrations();
        $this->bootEvents();
    }

    /**
     * تسجيل ملفات الإعدادات
     */
    protected function registerConfig(): void
    {
        $configPath = "{$this->modulePath()}/Config/config.php";

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, strtolower($this->moduleName()));
        }
    }

    /**
     * تسجيل الـ Repositories (يمكن Override في كل Module)
     */
    protected function registerRepositories(): void
    {
        // Override in child providers
    }

    /**
     * تحميل المسارات
     */
    protected function bootRoutes(): void
    {
        $routesPath = "{$this->modulePath()}/Routes";

        if (file_exists("{$routesPath}/api.php")) {
            $this->loadRoutesFrom("{$routesPath}/api.php");
        }

        if (file_exists("{$routesPath}/web.php")) {
            $this->loadRoutesFrom("{$routesPath}/web.php");
        }
    }

    /**
     * تحميل الـ Migrations
     */
    protected function bootMigrations(): void
    {
        $migrationsPath = "{$this->modulePath()}/Database/Migrations";

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * تسجيل الـ Events
     */
    protected function bootEvents(): void
    {
        // Override in child providers to register event listeners
    }
}
