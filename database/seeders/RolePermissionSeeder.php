<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache to avoid conflicts
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage users',
            'view dashboard',
            'manage vehicles',
            'manage drivers',
            'manage checkins',
            'manage maintenance',
            'manage expenses',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }

        // Create roles and assign permissions (guard: api)
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api'])
            ->givePermissionTo(Permission::all());

        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api'])
            ->givePermissionTo([
                'view dashboard',
                'manage vehicles',
                'manage drivers',
                'manage checkins',
                'manage maintenance',
                'manage expenses',
            ]);

        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'api'])
            ->givePermissionTo(['manage checkins']);

        Role::firstOrCreate(['name' => 'gate_security', 'guard_name' => 'api'])
            ->givePermissionTo(['manage checkins']);

        Role::firstOrCreate(['name' => 'vehicle_owner', 'guard_name' => 'api'])
            ->givePermissionTo([
                'view dashboard',
                'manage vehicles',
                'manage maintenance',
            ]);
        Role::firstOrCreate(['name' => 'visitor', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'api']);
    }
}
