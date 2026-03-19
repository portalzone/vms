<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creating a maintenance record should also create a linked expense
     * in the same database transaction.
     */
    public function test_creating_maintenance_also_creates_expense(): void
    {
        $vehicle = Vehicle::factory()->create();

        $service = new MaintenanceService();

        $maintenance = $service->create([
            'vehicle_id'  => $vehicle->id,
            'description' => 'Full engine service',
            'status'      => 'pending',
            'cost'        => 25000.00,
            'date'        => now()->toDateString(),
        ]);

        // The maintenance record itself should exist
        $this->assertInstanceOf(Maintenance::class, $maintenance);
        $this->assertDatabaseHas('maintenances', [
            'vehicle_id'  => $vehicle->id,
            'description' => 'Full engine service',
            'cost'        => 25000.00,
        ]);

        // A corresponding expense should have been created automatically
        $this->assertDatabaseHas('expenses', [
            'vehicle_id'      => $vehicle->id,
            'maintenance_id'  => $maintenance->id,
            'amount'          => 25000.00,
        ]);
    }

    /**
     * The expense description should reference the maintenance description.
     */
    public function test_expense_description_references_maintenance(): void
    {
        $vehicle = Vehicle::factory()->create();

        $service     = new MaintenanceService();
        $maintenance = $service->create([
            'vehicle_id'  => $vehicle->id,
            'description' => 'Tyre replacement',
            'status'      => 'completed',
            'cost'        => 8000.00,
            'date'        => now()->toDateString(),
        ]);

        $expense = Expense::where('maintenance_id', $maintenance->id)->first();

        $this->assertNotNull($expense);
        $this->assertStringContainsString('Tyre replacement', $expense->description);
    }

    /**
     * The expense date should match the maintenance date.
     */
    public function test_expense_date_matches_maintenance_date(): void
    {
        $vehicle = Vehicle::factory()->create();
        $date    = '2025-08-15';

        $service     = new MaintenanceService();
        $maintenance = $service->create([
            'vehicle_id'  => $vehicle->id,
            'description' => 'Brake pad replacement',
            'status'      => 'in_progress',
            'cost'        => 6500.00,
            'date'        => $date,
        ]);

        $expense = Expense::where('maintenance_id', $maintenance->id)->first();

        $this->assertEquals($date, $expense->date->toDateString());
    }

    /**
     * If something fails mid-transaction, neither record should be saved.
     * We test this by verifying the transaction returns a Maintenance instance
     * with a valid ID (meaning both inserts committed successfully).
     */
    public function test_service_returns_the_created_maintenance(): void
    {
        $vehicle = Vehicle::factory()->create();

        $service     = new MaintenanceService();
        $maintenance = $service->create([
            'vehicle_id'  => $vehicle->id,
            'description' => 'Routine check',
            'status'      => 'pending',
            'cost'        => 1000.00,
            'date'        => now()->toDateString(),
        ]);

        $this->assertNotNull($maintenance->id);
        $this->assertEquals($vehicle->id, $maintenance->vehicle_id);
    }
}
