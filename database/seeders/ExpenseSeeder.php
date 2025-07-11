<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Vehicle;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        Vehicle::factory()->count(5)->create();

        Expense::factory()->count(10)->create();
    }
}
