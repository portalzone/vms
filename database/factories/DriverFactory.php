<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'vehicle_id' => Vehicle::inRandomOrder()->first()?->id ?? Vehicle::factory(),
            'license_number' => strtoupper($this->faker->bothify('??###??')), // ✅ correct spelling
            'phone_number' => $this->faker->phoneNumber,
            'home_address' => $this->faker->address,
            'sex' => $this->faker->randomElement(['male', 'female', 'other']),
        ];
    }
}
