<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'description' => $this->faker->sentence,
            'amount' => $this->faker->randomFloat(2, 50, 1500),
            'date' => $this->faker->date(),
        ];
    }
}
