<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin',         'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'manager',       'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'driver',        'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'vehicle_owner', 'guard_name' => 'api']);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function driverUser(): array
    {
        $user    = User::factory()->create();
        $user->assignRole('driver');
        $vehicle = Vehicle::factory()->create();
        $driver  = Driver::factory()->create([
            'user_id'    => $user->id,
            'vehicle_id' => $vehicle->id,
        ]);
        return compact('user', 'vehicle', 'driver');
    }

    /**
     * An admin can see all trips in the system.
     */
    public function test_admin_can_list_all_trips(): void
    {
        Trip::factory()->count(4)->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/trips')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * A driver only sees trips assigned to them.
     */
    public function test_driver_sees_only_their_own_trips(): void
    {
        ['user' => $driverUser, 'driver' => $driver, 'vehicle' => $vehicle] = $this->driverUser();

        $otherDriver = Driver::factory()->create();

        Trip::factory()->create(['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id]);
        Trip::factory()->create(['driver_id' => $otherDriver->id]);

        $response = $this->actingAs($driverUser, 'sanctum')
            ->getJson('/api/trips')
            ->assertStatus(200);

        $trips = $response->json('data');
        foreach ($trips as $trip) {
            $this->assertEquals($driver->id, $trip['driver_id']);
        }
    }

    /**
     * Admin can create a trip record.
     */
    public function test_admin_can_create_trip(): void
    {
        $vehicle = Vehicle::factory()->create();
        $driver  = Driver::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/trips', [
                'driver_id'      => $driver->id,
                'vehicle_id'     => $vehicle->id,
                'start_location' => 'Lagos',
                'end_location'   => 'Abuja',
                'start_time'     => now()->toDateTimeString(),
                'end_time'       => now()->addHours(8)->toDateTimeString(),
                'status'         => 'completed',
                'amount'         => 12000,
            ])->assertStatus(200);

        $this->assertDatabaseHas('trips', [
            'start_location' => 'Lagos',
            'end_location'   => 'Abuja',
        ]);
    }

    /**
     * An admin can update a trip.
     */
    public function test_admin_can_update_trip(): void
    {
        $trip = Trip::factory()->create(['status' => 'ongoing']);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/trips/{$trip->id}", [
                'driver_id'      => $trip->driver_id,
                'vehicle_id'     => $trip->vehicle_id,
                'start_location' => $trip->start_location,
                'end_location'   => $trip->end_location,
                'start_time'     => $trip->start_time,
                'end_time'       => now()->toDateTimeString(),
                'status'         => 'completed',
                'amount'         => $trip->amount,
            ])->assertStatus(200);

        $this->assertDatabaseHas('trips', [
            'id'     => $trip->id,
            'status' => 'completed',
        ]);
    }

    /**
     * An admin can delete a trip record.
     */
    public function test_admin_can_delete_trip(): void
    {
        $trip = Trip::factory()->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }

    /**
     * A unauthenticated request is rejected.
     */
    public function test_unauthenticated_user_cannot_access_trips(): void
    {
        $this->getJson('/api/trips')
            ->assertStatus(401);
    }
}
