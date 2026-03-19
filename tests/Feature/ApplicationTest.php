<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The API base path responds correctly.
     */
    public function test_application_is_running(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Unauthenticated requests to protected routes return 401, not 302 or 500.
     * This checks that Sanctum is wired up and the API guard is working.
     */
    public function test_protected_routes_return_401_without_token(): void
    {
        $this->getJson('/api/vehicles')->assertStatus(401);
        $this->getJson('/api/drivers')->assertStatus(401);
        $this->getJson('/api/trips')->assertStatus(401);
        $this->getJson('/api/checkins')->assertStatus(401);
    }

    /**
     * The login and register endpoints are publicly accessible.
     */
    public function test_auth_endpoints_are_publicly_accessible(): void
    {
        // Should return 422 (validation error), not 401 (auth required)
        $this->postJson('/api/login', [])->assertStatus(422);
        $this->postJson('/api/register', [])->assertStatus(422);
    }
}
