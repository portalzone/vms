<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
        'manufacturer' => $this->faker->company,
        'model' => $this->faker->word,
        'year' => $this->faker->year,
        'plate_number' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{3}-[A-Z]{2}'),
    ];
    }
}
