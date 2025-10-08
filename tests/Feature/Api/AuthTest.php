<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default tenant for testing
        $this->tenant = Tenant::factory()->create();
    }

    /** @test */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'is_active' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_super_admin',
                        'tenant' => [
                            'id',
                            'name',
                            'domain',
                        ],
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'last_login' => now(),
        ]);
    }

    /** @test */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /** @test */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'is_active' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /** @test */
    public function test_user_can_register_successfully(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_id' => $this->tenant->id,
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_super_admin',
                        'tenant' => [
                            'id',
                            'name',
                            'domain',
                        ],
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function test_user_cannot_register_with_invalid_data(): void
    {
        $testCases = [
            // Missing required fields
            [
                'data' => [
                    'email' => 'test@example.com',
                    'password' => 'password123',
                ],
                'expected_errors' => ['name', 'password_confirmation'],
            ],
            // Invalid email format
            [
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'invalid-email',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expected_errors' => ['email'],
            ],
            // Password confirmation mismatch
            [
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'different_password',
                ],
                'expected_errors' => ['password'],
            ],
            // Short password
            [
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => '123',
                    'password_confirmation' => '123',
                ],
                'expected_errors' => ['password'],
            ],
        ];

        foreach ($testCases as $testCase) {
            $response = $this->postJson('/api/v1/auth/register', $testCase['data']);

            $response->assertStatus(422);

            foreach ($testCase['expected_errors'] as $field) {
                $response->assertJsonValidationErrors($field);
            }
        }
    }

    /** @test */
    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function test_user_can_logout_successfully(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        // Verify token is revoked by trying to access protected route
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    /** @test */
    public function test_user_can_get_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_super_admin',
                        'tenant' => [
                            'id',
                            'name',
                            'domain',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                ],
            ]);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $protectedRoutes = [
            ['method' => 'post', 'uri' => '/api/v1/auth/logout'],
            ['method' => 'get', 'uri' => '/api/v1/auth/me'],
            ['method' => 'get', 'uri' => '/api/v1/tenants'],
            ['method' => 'get', 'uri' => '/api/v1/pages'],
            ['method' => 'get', 'uri' => '/api/v1/utils/tenant'],
            ['method' => 'get', 'uri' => '/api/v1/utils/stats'],
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->json($route['method'], $route['uri']);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function test_login_validation_rules(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function test_user_last_login_is_updated_on_successful_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'last_login' => null,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertNull($user->fresh()->last_login);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertNotNull($user->fresh()->last_login);
    }
}
