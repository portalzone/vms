<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'license_number' => strtoupper($this->faker->bothify('??###??')),
            'phone_number' => $this->faker->phoneNumber,
            'home_address' => $this->faker->address,
            'sex' => $this->faker->randomElement(['male', 'female']),
            'vehicle_id' => Vehicle::inRandomOrder()->first()?->id ?? Vehicle::factory(), // assign or create
        ];
    }
}
