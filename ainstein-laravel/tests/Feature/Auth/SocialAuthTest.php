<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure OAuth (mock values for testing)
        Config::set('services.google', [
            'client_id' => 'mock-google-client-id',
            'client_secret' => 'mock-google-client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);

        Config::set('services.facebook', [
            'client_id' => 'mock-facebook-client-id',
            'client_secret' => 'mock-facebook-client-secret',
            'redirect' => 'http://localhost/auth/facebook/callback',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock Socialite user
     */
    private function mockSocialiteUser($data = [])
    {
        $user = Mockery::mock(SocialiteUser::class);

        $user->shouldReceive('getId')
            ->andReturn($data['id'] ?? 'mock_social_id_123456');

        $user->shouldReceive('getName')
            ->andReturn($data['name'] ?? 'John Doe');

        $user->shouldReceive('getEmail')
            ->andReturn($data['email'] ?? 'john.doe@example.com');

        $user->shouldReceive('getAvatar')
            ->andReturn($data['avatar'] ?? 'https://example.com/avatar.jpg');

        $user->shouldReceive('getNickname')
            ->andReturn($data['nickname'] ?? 'johndoe');

        return $user;
    }

    /** @test */
    public function test_redirect_to_google_provider_returns_redirect_response()
    {
        $response = $this->get('/auth/google');

        $response->assertStatus(302);
        $this->assertTrue(
            str_contains($response->headers->get('Location'), 'accounts.google.com') ||
            $response->headers->get('Location') === '/' // In case of mock
        );
    }

    /** @test */
    public function test_redirect_to_facebook_provider_returns_redirect_response()
    {
        $response = $this->get('/auth/facebook');

        $response->assertStatus(302);
    }

    /** @test */
    public function test_invalid_provider_returns_error()
    {
        $response = $this->get('/auth/twitter');

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Provider not supported');
    }

    /** @test */
    public function test_new_user_can_register_via_google_oauth()
    {
        // Mock Socialite
        $mockUser = $this->mockSocialiteUser([
            'id' => 'google_123456',
            'name' => 'Test User',
            'email' => 'testuser@gmail.com',
            'avatar' => 'https://lh3.googleusercontent.com/test',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        // Trigger callback
        $response = $this->get('/auth/google/callback');

        // Assertions
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        // Verify user created
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@gmail.com',
            'name' => 'Test User',
            'social_provider' => 'google',
            'social_id' => 'google_123456',
            'social_avatar' => 'https://lh3.googleusercontent.com/test',
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Verify user is authenticated
        $user = User::where('email', 'testuser@gmail.com')->first();
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());

        // Verify tenant created
        $this->assertNotNull($user->tenant_id);
        $this->assertDatabaseHas('tenants', [
            'id' => $user->tenant_id,
            'plan_type' => 'free',
            'status' => 'active',
        ]);

        // Verify email is verified
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function test_new_user_can_register_via_facebook_oauth()
    {
        // Mock Socialite
        $mockUser = $this->mockSocialiteUser([
            'id' => 'facebook_987654',
            'name' => 'Jane Smith',
            'email' => 'janesmith@facebook.com',
            'avatar' => 'https://graph.facebook.com/test/picture',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        // Trigger callback
        $response = $this->get('/auth/facebook/callback');

        // Assertions
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'janesmith@facebook.com',
            'name' => 'Jane Smith',
            'social_provider' => 'facebook',
            'social_id' => 'facebook_987654',
        ]);
    }

    /** @test */
    public function test_existing_user_can_login_via_social_oauth()
    {
        // Create existing user
        $tenant = Tenant::factory()->create();
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
            'tenant_id' => $tenant->id,
            'social_provider' => null,
            'social_id' => null,
        ]);

        // Mock Socialite with same email
        $mockUser = $this->mockSocialiteUser([
            'id' => 'google_existing_123',
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/existing',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        // Trigger callback
        $response = $this->get('/auth/google/callback');

        // Assertions
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success', 'Welcome back!');

        // Verify social info updated on existing user
        $existingUser->refresh();
        $this->assertEquals('google', $existingUser->social_provider);
        $this->assertEquals('google_existing_123', $existingUser->social_id);
        $this->assertEquals('https://lh3.googleusercontent.com/existing', $existingUser->social_avatar);

        // Verify no new tenant created
        $this->assertEquals($tenant->id, $existingUser->tenant_id);

        // Verify no duplicate user
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());

        // Verify user is authenticated
        $this->assertTrue(Auth::check());
        $this->assertEquals($existingUser->id, Auth::id());
    }

    /** @test */
    public function test_tenant_is_automatically_created_for_new_social_user()
    {
        $beforeTenantCount = Tenant::count();

        // Mock Socialite
        $mockUser = $this->mockSocialiteUser([
            'id' => 'tenant_test_123',
            'name' => 'Tenant Test',
            'email' => 'tenant@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        // Trigger callback
        $this->get('/auth/google/callback');

        // Verify tenant created
        $afterTenantCount = Tenant::count();
        $this->assertEquals($beforeTenantCount + 1, $afterTenantCount);

        // Verify tenant details
        $user = User::where('email', 'tenant@example.com')->first();
        $tenant = $user->tenant;

        $this->assertNotNull($tenant);
        $this->assertStringContainsString('Workspace', $tenant->name);
        $this->assertEquals('free', $tenant->plan_type);
        $this->assertEquals('active', $tenant->status);
        $this->assertEquals(10000, $tenant->tokens_monthly_limit);
        $this->assertEquals(0, $tenant->tokens_used_current);
    }

    /** @test */
    public function test_tenant_slug_is_unique()
    {
        // Create two users with similar names
        $mockUser1 = $this->mockSocialiteUser([
            'id' => 'slug_test_1',
            'name' => 'Test User',
            'email' => 'test1@example.com',
        ]);

        $mockUser2 = $this->mockSocialiteUser([
            'id' => 'slug_test_2',
            'name' => 'Test User',
            'email' => 'test2@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser1);
        $this->get('/auth/google/callback');

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser2);
        $this->get('/auth/google/callback');

        // Verify slugs are different
        $user1 = User::where('email', 'test1@example.com')->first();
        $user2 = User::where('email', 'test2@example.com')->first();

        $this->assertNotEquals($user1->tenant->slug, $user2->tenant->slug);
    }

    /** @test */
    public function test_social_user_has_verified_email()
    {
        $mockUser = $this->mockSocialiteUser([
            'email' => 'verified@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function test_social_user_has_random_password()
    {
        $mockUser = $this->mockSocialiteUser([
            'email' => 'password@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'password@example.com')->first();
        $this->assertNotNull($user->password_hash);
        $this->assertNotEmpty($user->password_hash);
    }

    /** @test */
    public function test_user_model_has_social_auth_method()
    {
        $tenant = Tenant::factory()->create();

        // User with social auth
        $socialUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'social_provider' => 'google',
            'social_id' => 'test_123',
        ]);

        // User without social auth
        $regularUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'social_provider' => null,
            'social_id' => null,
        ]);

        $this->assertTrue($socialUser->hasSocialAuth());
        $this->assertFalse($regularUser->hasSocialAuth());
    }

    /** @test */
    public function test_user_avatar_url_uses_social_avatar()
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'avatar@example.com',
            'social_avatar' => 'https://example.com/social-avatar.jpg',
            'avatar' => null,
        ]);

        $this->assertEquals('https://example.com/social-avatar.jpg', $user->avatar_url);
    }

    /** @test */
    public function test_social_callback_handles_invalid_state_exception()
    {
        // Mock Socialite to throw InvalidStateException
        Socialite::shouldReceive('driver->user')
            ->andThrow(new \Laravel\Socialite\Two\InvalidStateException('Invalid state'));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Authentication failed. Please try again.');
    }

    /** @test */
    public function test_social_callback_handles_general_exception()
    {
        // Mock Socialite to throw general exception
        Socialite::shouldReceive('driver->user')
            ->andThrow(new \Exception('General error'));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Authentication failed. Please try again.');
    }

    /** @test */
    public function test_api_redirect_to_provider_returns_json_with_redirect_url()
    {
        $response = $this->getJson('/api/v1/auth/social/google');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'redirect_url',
                'provider',
            ]);
    }

    /** @test */
    public function test_api_redirect_invalid_provider_returns_error()
    {
        $response = $this->getJson('/api/v1/auth/social/twitter');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Provider not supported']);
    }

    /** @test */
    public function test_api_callback_creates_user_and_returns_token()
    {
        // Mock Socialite for stateless
        $mockUser = $this->mockSocialiteUser([
            'id' => 'api_test_123',
            'name' => 'API Test User',
            'email' => 'api@example.com',
            'avatar' => 'https://example.com/api-avatar.jpg',
        ]);

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($mockUser);

        $response = $this->postJson('/api/v1/auth/social/google/callback');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'tenant' => [
                        'id',
                        'name',
                        'plan_type',
                    ],
                ],
            ]);

        // Verify token is returned
        $this->assertNotEmpty($response->json('token'));

        // Verify user created
        $this->assertDatabaseHas('users', [
            'email' => 'api@example.com',
            'name' => 'API Test User',
            'social_provider' => 'google',
        ]);
    }

    /** @test */
    public function test_api_callback_existing_user_returns_login_successful()
    {
        // Create existing user
        $tenant = Tenant::factory()->create();
        $existingUser = User::factory()->create([
            'email' => 'api-existing@example.com',
            'name' => 'API Existing User',
            'tenant_id' => $tenant->id,
        ]);

        // Mock Socialite
        $mockUser = $this->mockSocialiteUser([
            'id' => 'api_existing_123',
            'email' => 'api-existing@example.com',
        ]);

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($mockUser);

        $response = $this->postJson('/api/v1/auth/social/google/callback');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Login successful',
            ]);

        // Verify no duplicate user
        $this->assertEquals(1, User::where('email', 'api-existing@example.com')->count());
    }

    /** @test */
    public function test_social_user_is_created_with_owner_role()
    {
        $mockUser = $this->mockSocialiteUser([
            'email' => 'owner@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'owner@example.com')->first();
        $this->assertEquals('owner', $user->role);
    }

    /** @test */
    public function test_social_user_is_active_by_default()
    {
        $mockUser = $this->mockSocialiteUser([
            'email' => 'active@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'active@example.com')->first();
        $this->assertTrue($user->is_active);
    }

    /** @test */
    public function test_tenant_theme_config_is_set_for_social_user()
    {
        $mockUser = $this->mockSocialiteUser([
            'email' => 'theme@example.com',
            'name' => 'Theme Test',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'theme@example.com')->first();
        $tenant = $user->tenant;

        $this->assertIsArray($tenant->theme_config);
        $this->assertArrayHasKey('brandName', $tenant->theme_config);
        $this->assertArrayHasKey('primaryColor', $tenant->theme_config);
    }

    /** @test */
    public function test_social_user_without_name_uses_nickname()
    {
        $mockUser = $this->mockSocialiteUser([
            'id' => 'nickname_test',
            'name' => null,
            'nickname' => 'testuser',
            'email' => 'nickname@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'nickname@example.com')->first();
        $this->assertEquals('testuser', $user->name);
    }

    /** @test */
    public function test_social_user_without_name_or_nickname_defaults_to_user()
    {
        $mockUser = Mockery::mock(SocialiteUser::class);
        $mockUser->shouldReceive('getId')->andReturn('default_name_test');
        $mockUser->shouldReceive('getName')->andReturn(null);
        $mockUser->shouldReceive('getNickname')->andReturn(null);
        $mockUser->shouldReceive('getEmail')->andReturn('default@example.com');
        $mockUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver->user')
            ->andReturn($mockUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'default@example.com')->first();
        $this->assertEquals('User', $user->name);
    }
}
