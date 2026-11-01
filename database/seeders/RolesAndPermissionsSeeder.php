<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionsByModule = [
            'users' => ['view', 'create', 'update', 'delete'],
            'settings' => ['view', 'update'],
            'owners' => ['view', 'create', 'update', 'delete'],
            'patients' => ['view', 'create', 'update', 'delete'],
            'appointments' => ['view', 'create', 'update', 'delete'],
            'consultations' => ['view', 'create', 'update', 'finalize'],
            'vaccinations' => ['view', 'create'],
            'products' => ['view', 'create', 'update', 'delete'],
            'inventory' => ['view', 'manage'],
            'invoices' => ['view', 'create', 'void'],
            'payments' => ['view', 'create'],
            'cash' => ['view', 'open', 'close'],
            'lab_orders' => ['view', 'manage'],
            'reports' => ['view'],
            'activity' => ['view'],
        ];

        $allPermissions = [];

        foreach ($permissionsByModule as $module => $actions) {
            foreach ($actions as $action) {
                $allPermissions[] = "{$module}.{$action}";

                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }

        $rolePermissions = [
            'admin' => $allPermissions,
            'veterinario' => [
                'owners.view',
                'patients.view',
                'patients.create',
                'patients.update',
                'appointments.view',
                'appointments.create',
                'appointments.update',
                'consultations.view',
                'consultations.create',
                'consultations.update',
                'consultations.finalize',
                'vaccinations.view',
                'vaccinations.create',
                'lab_orders.view',
                'reports.view',
            ],
            'recepcion' => [
                'owners.view',
                'owners.create',
                'owners.update',
                'patients.view',
                'patients.create',
                'patients.update',
                'appointments.view',
                'appointments.create',
                'appointments.update',
                'invoices.view',
                'invoices.create',
                'payments.view',
                'payments.create',
                'cash.view',
                'cash.open',
                'cash.close',
                'reports.view',
            ],
            'almacen' => [
                'products.view',
                'products.create',
                'products.update',
                'products.delete',
                'inventory.view',
                'inventory.manage',
                'reports.view',
            ],
            'laboratorio' => [
                'patients.view',
                'consultations.view',
                'lab_orders.view',
                'lab_orders.manage',
                'reports.view',
            ],
            'contador' => [
                'invoices.view',
                'payments.view',
                'cash.view',
                'reports.view',
            ],
            'cliente' => [],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
