<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'subdomain' => 'test-tenant',
            'status' => 'active',
        ]);

        // Create a test user
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function token_is_created_with_expiration()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'user',
                'token'
            ]
        ]);

        // Verify token exists in database with expiration
        $token = $this->user->tokens()->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);

        // Verify expiration is approximately 24 hours from now
        $expectedExpiration = now()->addHours(24);
        $this->assertTrue(
            $token->expires_at->diffInMinutes($expectedExpiration) < 2,
            'Token expiration should be approximately 24 hours from now'
        );
    }

    /** @test */
    public function expired_token_is_rejected()
    {
        // Create a token that's already expired
        $token = $this->user->createToken('test-token', ['*'], now()->subHour());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->getJson('/api/v1/auth/me');

        // Sanctum automatically rejects expired tokens
        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        // Token still exists in database (Sanctum doesn't auto-delete)
        // Our middleware would delete it, but Sanctum rejects it before middleware runs
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id
        ]);
    }

    /** @test */
    public function valid_token_allows_access()
    {
        // Create a token that's still valid
        $token = $this->user->createToken('test-token', ['*'], now()->addHours(24));

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user'
            ]
        ]);
    }

    /** @test */
    public function token_expiration_is_configurable()
    {
        // Test that config value is set
        $expiration = config('sanctum.expiration');
        $this->assertEquals(1440, $expiration, 'Sanctum expiration should be 1440 minutes (24 hours)');
    }

    /** @test */
    public function all_auth_endpoints_create_tokens_with_expiration()
    {
        // Test login endpoint
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $loginResponse->assertStatus(200);

        // Verify login token has expiration
        $loginToken = $this->user->tokens()->latest()->first();
        $this->assertNotNull($loginToken->expires_at);

        // Test register endpoint
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_id' => $this->tenant->id,
        ]);
        $registerResponse->assertStatus(201);

        // Verify register token has expiration
        $newUser = User::where('email', 'newuser@example.com')->first();
        $registerToken = $newUser->tokens()->first();
        $this->assertNotNull($registerToken->expires_at);
    }
}
