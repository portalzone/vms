<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie needs guard-aware roles set up before each test
        Role::firstOrCreate(['name' => 'admin',          'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'manager',        'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'driver',         'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'gate_security',  'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'vehicle_owner',  'guard_name' => 'api']);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function driver(): User
    {
        $user = User::factory()->create();
        $user->assignRole('driver');
        return $user;
    }

    private function vehicleOwner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('vehicle_owner');
        return $user;
    }

    // ── Listing ──────────────────────────────────────────────────────────────

    /**
     * An admin can see all vehicles.
     */
    public function test_admin_can_list_vehicles(): void
    {
        Vehicle::factory()->count(3)->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/vehicles')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * A guest cannot access the vehicle list.
     */
    public function test_unauthenticated_user_cannot_list_vehicles(): void
    {
        $this->getJson('/api/vehicles')
            ->assertStatus(401);
    }

    // ── Creating ─────────────────────────────────────────────────────────────

    /**
     * An admin can add an organisation-owned vehicle.
     */
    public function test_admin_can_create_organisation_vehicle(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/vehicles', [
                'manufacturer'   => 'Toyota',
                'model'          => 'Hiace',
                'year'           => 2021,
                'plate_number'   => 'ABC-001',
                'ownership_type' => 'organization',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('vehicle.plate_number', 'ABC-001');

        $this->assertDatabaseHas('vehicles', ['plate_number' => 'ABC-001']);
    }

    /**
     * An individual vehicle with type vehicle_owner requires an owner_id.
     */
    public function test_individual_vehicle_owner_type_requires_owner_id(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/vehicles', [
                'manufacturer'    => 'Honda',
                'model'           => 'Civic',
                'year'            => 2020,
                'plate_number'    => 'XYZ-999',
                'ownership_type'  => 'individual',
                'individual_type' => 'vehicle_owner',
                // owner_id deliberately omitted
            ])->assertStatus(422);
    }

    /**
     * Plate numbers must be unique across the fleet.
     */
    public function test_duplicate_plate_number_is_rejected(): void
    {
        Vehicle::factory()->create(['plate_number' => 'TAKEN-01']);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/vehicles', [
                'manufacturer'   => 'Ford',
                'model'          => 'Transit',
                'year'           => 2022,
                'plate_number'   => 'TAKEN-01',
                'ownership_type' => 'organization',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['plate_number']);
    }

    /**
     * A driver cannot create a vehicle.
     */
    public function test_driver_cannot_create_vehicle(): void
    {
        $this->actingAs($this->driver(), 'sanctum')
            ->postJson('/api/vehicles', [
                'manufacturer'   => 'Kia',
                'model'          => 'Sorento',
                'year'           => 2023,
                'plate_number'   => 'DRV-001',
                'ownership_type' => 'organization',
            ])->assertStatus(403);
    }

    // ── Updating ─────────────────────────────────────────────────────────────

    /**
     * An admin can update a vehicle's details.
     */
    public function test_admin_can_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'OLD-001']);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/vehicles/{$vehicle->id}", [
                'manufacturer'   => 'Toyota',
                'model'          => 'Coaster',
                'year'           => 2022,
                'plate_number'   => 'NEW-001',
                'ownership_type' => 'organization',
            ])->assertStatus(200);

        $this->assertDatabaseHas('vehicles', ['plate_number' => 'NEW-001']);
    }

    // ── Deleting ─────────────────────────────────────────────────────────────

    /**
     * Only admins can delete vehicles.
     */
    public function test_admin_can_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/vehicles/{$vehicle->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    /**
     * A vehicle owner cannot delete a vehicle they own.
     */
    public function test_vehicle_owner_cannot_delete_vehicle(): void
    {
        $owner   = $this->vehicleOwner();
        $vehicle = Vehicle::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/vehicles/{$vehicle->id}")
            ->assertStatus(403);
    }

    // ── Special endpoints ────────────────────────────────────────────────────

    /**
     * The plate search endpoint returns matching vehicles.
     */
    public function test_plate_search_returns_matching_vehicles(): void
    {
        Vehicle::factory()->create(['plate_number' => 'LGS-101']);
        Vehicle::factory()->create(['plate_number' => 'ABJ-202']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/vehicles/search-by-plate?q=LGS')
            ->assertStatus(200);
    }
}
