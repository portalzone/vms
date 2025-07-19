<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call([
            // DriverSeeder::class,
            // VehicleSeeder::class,
            // CheckInOutSeeder::class,
            // MaintenanceSeeder::class,
            // ExpenseSeeder::class,
            UserSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
