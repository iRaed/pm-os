<?php

declare(strict_types=1);

namespace Modules\Foundation;

use App\Core\ModuleServiceProvider;

class FoundationServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Foundation';
    }

    public function register(): void
    {
        parent::register();

        // Register repository bindings
        $this->app->bind(
            \Modules\Foundation\Repositories\PropertyRepositoryInterface::class,
            \Modules\Foundation\Repositories\PropertyRepository::class
        );

        $this->app->bind(
            \Modules\Foundation\Repositories\UnitRepositoryInterface::class,
            \Modules\Foundation\Repositories\UnitRepository::class
        );

        $this->app->bind(
            \Modules\Foundation\Repositories\OwnerRepositoryInterface::class,
            \Modules\Foundation\Repositories\OwnerRepository::class
        );
    }
}
