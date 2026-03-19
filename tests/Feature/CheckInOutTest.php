<?php

namespace Tests\Feature;

use App\Models\CheckInOut;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckInOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin',         'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'manager',       'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'gate_security', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'driver',        'guard_name' => 'api']);
    }

    private function gateSecurity(): User
    {
        $user = User::factory()->create();
        $user->assignRole('gate_security');
        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    /**
     * Create a vehicle with an assigned driver, ready for check-in tests.
     */
    private function vehicleWithDriver(): array
    {
        $driverUser = User::factory()->create();
        $vehicle    = Vehicle::factory()->create();
        $driver     = Driver::factory()->create([
            'user_id'    => $driverUser->id,
            'vehicle_id' => $vehicle->id,
        ]);

        return compact('vehicle', 'driver', 'driverUser');
    }

    // ── Check-in ─────────────────────────────────────────────────────────────

    /**
     * Gate security can check in a vehicle that has a driver assigned.
     */
    public function test_gate_security_can_check_in_vehicle(): void
    {
        ['vehicle' => $vehicle] = $this->vehicleWithDriver();
        $operator = $this->gateSecurity();

        $this->actingAs($operator, 'sanctum')
            ->postJson('/api/checkins', ['vehicle_id' => $vehicle->id])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Check-in successful.');

        $this->assertDatabaseHas('check_in_outs', [
            'vehicle_id'      => $vehicle->id,
            'checked_out_at'  => null,
        ]);
    }

    /**
     * A vehicle without a driver assigned cannot be checked in.
     */
    public function test_cannot_check_in_vehicle_without_driver(): void
    {
        $vehicle  = Vehicle::factory()->create(); // no driver attached
        $operator = $this->gateSecurity();

        $this->actingAs($operator, 'sanctum')
            ->postJson('/api/checkins', ['vehicle_id' => $vehicle->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No driver is assigned to this vehicle.');
    }

    /**
     * A vehicle that is already checked in cannot be checked in again.
     */
    public function test_cannot_check_in_vehicle_that_is_already_inside(): void
    {
        ['vehicle' => $vehicle, 'driver' => $driver] = $this->vehicleWithDriver();
        $operator = $this->gateSecurity();

        // First check-in
        CheckInOut::create([
            'vehicle_id'     => $vehicle->id,
            'driver_id'      => $driver->id,
            'checked_in_at'  => now(),
            'checked_in_by'  => $operator->id,
        ]);

        // Second attempt — should be rejected
        $this->actingAs($operator, 'sanctum')
            ->postJson('/api/checkins', ['vehicle_id' => $vehicle->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This vehicle is already checked in.');
    }

    // ── Check-out ────────────────────────────────────────────────────────────

    /**
     * Gate security can close an open check-in.
     */
    public function test_gate_security_can_check_out_vehicle(): void
    {
        ['vehicle' => $vehicle, 'driver' => $driver] = $this->vehicleWithDriver();
        $operator = $this->gateSecurity();

        $record = CheckInOut::create([
            'vehicle_id'    => $vehicle->id,
            'driver_id'     => $driver->id,
            'checked_in_at' => now(),
            'checked_in_by' => $operator->id,
        ]);

        $this->actingAs($operator, 'sanctum')
            ->postJson("/api/checkins/{$record->id}/checkout")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Check-out successful.');

        $this->assertDatabaseHas('check_in_outs', [
            'id'             => $record->id,
        ]);

        // checked_out_at should now be populated
        $record->refresh();
        $this->assertNotNull($record->checked_out_at);
    }

    /**
     * Trying to check out a record that is already closed returns 404.
     */
    public function test_cannot_check_out_already_checked_out_record(): void
    {
        ['vehicle' => $vehicle, 'driver' => $driver] = $this->vehicleWithDriver();
        $operator = $this->gateSecurity();

        $record = CheckInOut::create([
            'vehicle_id'      => $vehicle->id,
            'driver_id'       => $driver->id,
            'checked_in_at'   => now()->subHour(),
            'checked_out_at'  => now(),      // already closed
            'checked_in_by'   => $operator->id,
            'checked_out_by'  => $operator->id,
        ]);

        $this->actingAs($operator, 'sanctum')
            ->postJson("/api/checkins/{$record->id}/checkout")
            ->assertStatus(404);
    }

    // ── Listing ──────────────────────────────────────────────────────────────

    /**
     * Admin can retrieve a paginated list of all check-in/out records.
     */
    public function test_admin_can_list_checkin_records(): void
    {
        ['vehicle' => $vehicle, 'driver' => $driver] = $this->vehicleWithDriver();

        CheckInOut::factory()->count(5)->create([
            'vehicle_id' => $vehicle->id,
            'driver_id'  => $driver->id,
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/checkins')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /**
     * An unauthenticated request gets a 401.
     */
    public function test_unauthenticated_user_cannot_list_checkins(): void
    {
        $this->getJson('/api/checkins')
            ->assertStatus(401);
    }

    // ── Latest endpoint ───────────────────────────────────────────────────────

    /**
     * The latest endpoint returns the most recent record for a vehicle.
     */
    public function test_latest_returns_most_recent_checkin_for_vehicle(): void
    {
        ['vehicle' => $vehicle, 'driver' => $driver] = $this->vehicleWithDriver();

        CheckInOut::create([
            'vehicle_id'    => $vehicle->id,
            'driver_id'     => $driver->id,
            'checked_in_at' => now()->subDay(),
        ]);

        $latest = CheckInOut::create([
            'vehicle_id'    => $vehicle->id,
            'driver_id'     => $driver->id,
            'checked_in_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/checkins/latest?vehicle_id={$vehicle->id}")
            ->assertStatus(200)
            ->assertJsonPath('id', $latest->id);
    }

    /**
     * Calling the latest endpoint without a vehicle_id returns 400.
     */
    public function test_latest_requires_vehicle_id(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/checkins/latest')
            ->assertStatus(400);
    }
}
