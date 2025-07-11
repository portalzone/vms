<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Maintenance;
use App\Models\Vehicle;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        // Create vehicles first
        $vehicles = Vehicle::factory()->count(5)->create();

        // Create maintenances for random vehicles
        foreach ($vehicles as $vehicle) {
            Maintenance::factory()
                ->count(rand(2, 4)) // Assign 2-4 maintenances per vehicle
                ->create([
                    'vehicle_id' => $vehicle->id,
                ]);
        }
    }
}
