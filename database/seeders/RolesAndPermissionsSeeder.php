<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * الأدوار والصلاحيات — يُشغّل لكل Tenant عند الإنشاء
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── الصلاحيات ───────────────────────────────

        $permissions = [
            // العقارات
            'properties.view', 'properties.create', 'properties.edit', 'properties.delete',
            'properties.manage_settings',

            // الوحدات
            'units.view', 'units.create', 'units.edit', 'units.delete',

            // الملاك
            'owners.view', 'owners.create', 'owners.edit', 'owners.delete',
            'owners.view_financials',

            // المستأجرون
            'residents.view', 'residents.create', 'residents.edit', 'residents.delete',
            'residents.blacklist',

            // العقود
            'leases.view', 'leases.create', 'leases.edit', 'leases.delete',
            'leases.approve', 'leases.terminate', 'leases.renew',

            // الفواتير
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'invoices.write_off', 'invoices.send',

            // المدفوعات
            'payments.view', 'payments.create', 'payments.confirm', 'payments.refund',

            // أوامر العمل
            'work_orders.view', 'work_orders.create', 'work_orders.edit', 'work_orders.delete',
            'work_orders.assign', 'work_orders.verify', 'work_orders.close',

            // الصيانة الوقائية
            'pm_plans.view', 'pm_plans.create', 'pm_plans.edit', 'pm_plans.delete',

            // المقاولون
            'contractors.view', 'contractors.create', 'contractors.edit', 'contractors.delete',

            // الفحوصات
            'inspections.view', 'inspections.create', 'inspections.edit',

            // المستندات
            'documents.view', 'documents.upload', 'documents.delete', 'documents.verify',

            // التقارير
            'reports.view_operational', 'reports.view_financial', 'reports.view_owner',
            'reports.export',

            // المالية
            'finance.view_ledger', 'finance.create_entry', 'finance.approve_expense',
            'finance.view_owner_statements', 'finance.manage_budgets',

            // الحوكمة
            'compliance.view', 'compliance.manage',
            'risks.view', 'risks.manage',

            // جمعيات الملاك
            'hoa.view', 'hoa.manage', 'hoa.manage_meetings',

            // الإعدادات
            'settings.view', 'settings.manage',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.manage',

            // التسويق
            'marketing.view', 'marketing.manage_listings',

            // الذكاء الاصطناعي
            'ai.use_assistant', 'ai.generate_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ─── الأدوار ─────────────────────────────────

        // مدير عام — كل الصلاحيات
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // مدير عقارات
        $propertyManager = Role::firstOrCreate(['name' => 'property_manager', 'guard_name' => 'web']);
        $propertyManager->givePermissionTo([
            'properties.view', 'properties.edit',
            'units.view', 'units.create', 'units.edit',
            'owners.view',
            'residents.view', 'residents.create', 'residents.edit',
            'leases.view', 'leases.create', 'leases.edit', 'leases.renew',
            'invoices.view', 'invoices.create', 'invoices.send',
            'payments.view', 'payments.create', 'payments.confirm',
            'work_orders.view', 'work_orders.create', 'work_orders.edit', 'work_orders.assign', 'work_orders.verify',
            'pm_plans.view', 'pm_plans.create', 'pm_plans.edit',
            'contractors.view',
            'inspections.view', 'inspections.create', 'inspections.edit',
            'documents.view', 'documents.upload',
            'reports.view_operational', 'reports.view_financial',
            'marketing.view', 'marketing.manage_listings',
            'ai.use_assistant', 'ai.generate_reports',
        ]);

        // محاسب
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->givePermissionTo([
            'properties.view', 'units.view', 'owners.view', 'owners.view_financials',
            'residents.view',
            'leases.view',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.send', 'invoices.write_off',
            'payments.view', 'payments.create', 'payments.confirm', 'payments.refund',
            'finance.view_ledger', 'finance.create_entry', 'finance.approve_expense',
            'finance.view_owner_statements', 'finance.manage_budgets',
            'reports.view_financial', 'reports.view_owner', 'reports.export',
            'documents.view', 'documents.upload',
        ]);

        // محصّل
        $collector = Role::firstOrCreate(['name' => 'collector', 'guard_name' => 'web']);
        $collector->givePermissionTo([
            'properties.view', 'units.view', 'residents.view',
            'leases.view',
            'invoices.view', 'invoices.send',
            'payments.view', 'payments.create',
            'reports.view_operational',
            'documents.view',
        ]);

        // فني صيانة
        $technician = Role::firstOrCreate(['name' => 'technician', 'guard_name' => 'web']);
        $technician->givePermissionTo([
            'properties.view', 'units.view',
            'work_orders.view', 'work_orders.edit',
            'pm_plans.view',
            'inspections.view', 'inspections.create', 'inspections.edit',
            'documents.view', 'documents.upload',
        ]);

        // مشرف صيانة
        $maintenanceSupervisor = Role::firstOrCreate(['name' => 'maintenance_supervisor', 'guard_name' => 'web']);
        $maintenanceSupervisor->givePermissionTo([
            'properties.view', 'units.view', 'residents.view',
            'work_orders.view', 'work_orders.create', 'work_orders.edit',
            'work_orders.assign', 'work_orders.verify', 'work_orders.close',
            'pm_plans.view', 'pm_plans.create', 'pm_plans.edit',
            'contractors.view', 'contractors.create', 'contractors.edit',
            'inspections.view', 'inspections.create', 'inspections.edit',
            'documents.view', 'documents.upload',
            'reports.view_operational',
        ]);

        // موظف استقبال / خدمة عملاء
        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionist->givePermissionTo([
            'properties.view', 'units.view', 'residents.view',
            'leases.view',
            'work_orders.view', 'work_orders.create',
            'documents.view',
            'ai.use_assistant',
        ]);
    }
}
