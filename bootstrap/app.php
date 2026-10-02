<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Tenant routes (routes/tenant.php) are mapped by
        // App\Providers\TenancyServiceProvider::mapRoutes().
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tenant routes reference the stancl tenancy middleware classes
        // directly (see routes/tenant.php) instead of a named group, so no
        // 'tenant' group is registered here — a group name leaks into the
        // terminate phase and throws "Class \"tenant\" does not exist".

        // 'central_admin' alias (referenced by routes/web.php admin routes).
        $middleware->alias([
            'central_admin' => \App\Http\Middleware\EnsureCentralAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
