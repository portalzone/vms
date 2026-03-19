<?php

namespace Tests\Unit;

use App\Models\Driver;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Maintenance;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A vehicle knows about its driver.
     */
    public function test_vehicle_has_one_driver(): void
    {
        $vehicle = Vehicle::factory()->create();
        $driver  = Driver::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertInstanceOf(Driver::class, $vehicle->driver);
        $this->assertEquals($driver->id, $vehicle->driver->id);
    }

    /**
     * A driver belongs to a user account.
     */
    public function test_driver_belongs_to_user(): void
    {
        $user   = User::factory()->create();
        $driver = Driver::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $driver->user->id);
    }

    /**
     * A vehicle can have multiple trips logged against it.
     */
    public function test_vehicle_has_many_trips(): void
    {
        $vehicle = Vehicle::factory()->create();
        $driver  = Driver::factory()->create(['vehicle_id' => $vehicle->id]);

        Trip::factory()->count(3)->create([
            'vehicle_id' => $vehicle->id,
            'driver_id'  => $driver->id,
        ]);

        $this->assertCount(3, $vehicle->trips);
    }

    /**
     * A maintenance record is linked to its vehicle.
     */
    public function test_maintenance_belongs_to_vehicle(): void
    {
        $vehicle     = Vehicle::factory()->create();
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertEquals($vehicle->id, $maintenance->vehicle->id);
    }

    /**
     * An expense can reference a maintenance record.
     */
    public function test_expense_belongs_to_maintenance(): void
    {
        $vehicle     = Vehicle::factory()->create();
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $vehicle->id]);

        $expense = Expense::factory()->create([
            'vehicle_id'     => $vehicle->id,
            'maintenance_id' => $maintenance->id,
        ]);

        $this->assertEquals($maintenance->id, $expense->maintenance->id);
    }

    /**
     * A trip can have one income record attached.
     */
    public function test_trip_has_one_income(): void
    {
        $vehicle = Vehicle::factory()->create();
        $driver  = Driver::factory()->create(['vehicle_id' => $vehicle->id]);

        $trip = Trip::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id'  => $driver->id,
        ]);

        $income = Income::factory()->create([
            'trip_id'    => $trip->id,
            'vehicle_id' => $vehicle->id,
            'driver_id'  => $driver->id,
        ]);

        $this->assertEquals($income->id, $trip->income->id);
    }

    /**
     * A user can have a driver profile.
     */
    public function test_user_has_one_driver_profile(): void
    {
        $user   = User::factory()->create();
        $driver = Driver::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Driver::class, $user->driver);
        $this->assertEquals($driver->id, $user->driver->id);
    }
}
