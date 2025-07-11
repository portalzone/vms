<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckInOutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory(),
            'checked_in_at' => now(),
            'checked_out_at' => now()->addHours(rand(1, 8)),
        ];
    }
}
