<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckInOut;
use App\Models\Driver;
use App\Models\Vehicle;

class CheckInOutSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there are drivers and vehicles first
        Driver::factory()->count(5)->create();
        Vehicle::factory()->count(5)->create();

        CheckInOut::factory()->count(15)->create();
    }
}
