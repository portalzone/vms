<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceFactory extends Factory
{
    public function definition(): array
    {
       return [
    'description' => $this->faker->sentence,
    'status' => $this->faker->randomElement(['Pending', 'In Progress', 'Completed']),
    'cost' => $this->faker->randomFloat(2, 100, 2000),
    'date' => $this->faker->date(),
];

    }
}
