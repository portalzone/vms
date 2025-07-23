<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    public function create(array $data): Maintenance
    {
        return DB::transaction(function () use ($data) {
            $maintenance = Maintenance::create($data);

            Expense::create([
                'vehicle_id' => $maintenance->vehicle_id,
                'maintenance_id' => $maintenance->id,
                'description' => 'Maintenance: ' . $maintenance->description,
                'amount' => $maintenance->cost,
                'date' => $maintenance->date,
            ]);

            return $maintenance;
        });
    }
}
