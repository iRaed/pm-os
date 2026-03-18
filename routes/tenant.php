<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Tenant Web Routes
|--------------------------------------------------------------------------
| يتم تحميلها لكل Tenant (شركة إدارة أملاك)
| Middleware: web, tenant, auth
*/

Route::middleware(['web', 'tenant'])->group(function () {

    // ─── Auth Routes (Guest) ─────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
        Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'store']);
    });

    // ─── Authenticated Routes ────────────────────────
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'destroy'])->name('logout');

        // Dashboard
        Route::get('/', fn() => Inertia::render('Dashboard'))->name('dashboard');

        // Properties
        Route::get('/properties', fn() => Inertia::render('Properties/Index'))->name('properties.index');
        Route::get('/properties/create', fn() => Inertia::render('Properties/Create'))->name('properties.create');
        Route::get('/properties/{property}', fn($property) => Inertia::render('Properties/Show', [
            'propertyId' => $property,
        ]))->name('properties.show');

        // Units
        Route::get('/units', fn() => Inertia::render('Units/Index'))->name('units.index');

        // Owners
        Route::get('/owners', fn() => Inertia::render('Owners/Index'))->name('owners.index');
        Route::get('/owners/{owner}', fn($owner) => Inertia::render('Owners/Show', [
            'ownerId' => $owner,
        ]))->name('owners.show');

        // Residents
        Route::get('/residents', fn() => Inertia::render('Residents/Index'))->name('residents.index');

        // Leases
        Route::get('/leases', fn() => Inertia::render('Leases/Index'))->name('leases.index');
        Route::get('/leases/create', fn() => Inertia::render('Leases/Create'))->name('leases.create');

        // Invoices & Collection
        Route::get('/invoices', fn() => Inertia::render('Collection/Invoices'))->name('invoices.index');
        Route::get('/collection', fn() => Inertia::render('Collection/Dashboard'))->name('collection.dashboard');

        // Maintenance
        Route::get('/work-orders', fn() => Inertia::render('Maintenance/WorkOrders'))->name('work-orders.index');
        Route::get('/work-orders/create', fn() => Inertia::render('Maintenance/CreateWorkOrder'))->name('work-orders.create');
        Route::get('/pm-plans', fn() => Inertia::render('Maintenance/PmPlans'))->name('pm-plans.index');
        Route::get('/contractors', fn() => Inertia::render('Maintenance/Contractors'))->name('contractors.index');
        Route::get('/inspections', fn() => Inertia::render('Maintenance/Inspections'))->name('inspections.index');

        // Finance
        Route::get('/finance', fn() => Inertia::render('Finance/Dashboard'))->name('finance.dashboard');
        Route::get('/finance/owner-statements', fn() => Inertia::render('Finance/OwnerStatements'))->name('finance.owner-statements');

        // Reports
        Route::get('/reports', fn() => Inertia::render('Reports/Index'))->name('reports.index');

        // Settings
        Route::get('/settings', fn() => Inertia::render('Settings/General'))->name('settings.index');
        Route::get('/settings/users', fn() => Inertia::render('Settings/Users'))->name('settings.users');
        Route::get('/settings/roles', fn() => Inertia::render('Settings/Roles'))->name('settings.roles');
    });
});
