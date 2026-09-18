<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = [
            'departments',
            'financial-movements',
            'financial-projections',
            'purchases',
            'inventory-items',
            'reports',
            'users',
        ];

        $actions = ['view', 'create', 'update', 'delete'];
        $permissions = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = "{$action} {$module}";
            }
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $contador = Role::firstOrCreate([
            'name' => 'contador',
            'guard_name' => 'web',
        ]);

        $jefeDepartamento = Role::firstOrCreate([
            'name' => 'jefe_departamento',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions($permissions);
        $contador->syncPermissions([
            'view departments',
            'view financial-movements',
            'create financial-movements',
            'update financial-movements',
            'delete financial-movements',
            'view purchases',
            'update purchases',
            'view financial-projections',
            'create financial-projections',
            'update financial-projections',
            'view inventory-items',
            'view reports',
            'create reports',
            'update reports',
        ]);
        $jefeDepartamento->syncPermissions([
            'view departments',
            'view financial-movements',
            'create financial-movements',
            'update financial-movements',
            'view purchases',
            'create purchases',
            'update purchases',
            'view financial-projections',
            'create financial-projections',
            'update financial-projections',
            'view inventory-items',
            'create inventory-items',
            'update inventory-items',
            'view reports',
            'create reports',
            'update reports',
        ]);

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@velvet.test'],
            [
                'name' => 'Admin Velvet',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $contadorUser = User::updateOrCreate(
            ['email' => 'contador@velvet.test'],
            [
                'name' => 'Contador Velvet',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $adminUser->syncRoles([$admin]);
        $contadorUser->syncRoles([$contador]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
