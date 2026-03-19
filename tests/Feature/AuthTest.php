<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A fresh user can register and gets back a token.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    /**
     * Registration requires all fields to be present and valid.
     */
    public function test_registration_fails_without_required_fields(): void
    {
        $this->postJson('/api/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Two users cannot share the same email address.
     */
    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name'                  => 'Another User',
            'email'                 => 'taken@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * A registered user can log in with the right credentials.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('mypassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@example.com',
            'password' => 'mypassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    /**
     * Wrong password means no token.
     */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $this->postJson('/api/login', [
            'email'    => 'user@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    /**
     * /me returns the currently authenticated user.
     */
    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('user.email', $user->email);
    }

    /**
     * Unauthenticated requests to protected endpoints get a 401.
     */
    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')
            ->assertStatus(401);
    }

    /**
     * Logging out revokes the token so it can't be used again.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out']);
    }
}
