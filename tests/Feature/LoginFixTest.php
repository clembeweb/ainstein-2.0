<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantOAuthProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginFixTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Login Tenant',
            'subdomain' => 'testlogin',
            'email' => 'login@test.com',
            'is_active' => true,
        ]);

        // Create test user
        $this->user = User::create([
            'email' => 'logintest@example.com',
            'password_hash' => bcrypt('testpassword'),
            'name' => 'Login Test User',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test that the login page loads without errors
     */
    public function test_login_page_loads_successfully()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test that tenant_oauth_providers table exists and is queryable
     */
    public function test_oauth_providers_table_is_accessible()
    {
        // Verify table exists
        $this->assertDatabaseMissing('tenant_oauth_providers', ['id' => 999999]);

        // Create a test provider
        $provider = TenantOAuthProvider::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'google',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('tenant_oauth_providers', [
            'tenant_id' => $this->tenant->id,
            'provider' => 'google',
        ]);

        // Verify the model uses the correct table name
        $this->assertEquals('tenant_oauth_providers', $provider->getTable());
    }

    /**
     * Test that the User model correctly maps password to password_hash
     */
    public function test_user_password_mapping_works()
    {
        // Create user using password accessor
        $user = new User();
        $user->email = 'accessor@test.com';
        $user->name = 'Accessor Test';
        $user->tenant_id = $this->tenant->id;
        $user->password = bcrypt('accessor_password');
        $user->is_active = true;
        $user->save();

        // Verify it saved to password_hash column
        $this->assertDatabaseHas('users', [
            'email' => 'accessor@test.com',
        ]);

        // Verify password accessor returns password_hash
        $this->assertNotNull($user->password);
        $this->assertEquals($user->password_hash, $user->password);
    }

    /**
     * Test complete login flow with API
     */
    public function test_api_login_works_with_password_hash()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'testpassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);
    }

    /**
     * Test that OAuth service can query providers without errors
     */
    public function test_oauth_service_queries_correct_table()
    {
        // Query with tenant_id null (as per original error)
        $providers = TenantOAuthProvider::where('tenant_id', null)
            ->where('is_active', true)
            ->get();

        // Should not throw error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $providers);
        $this->assertCount(0, $providers);

        // Query with actual tenant
        TenantOAuthProvider::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'facebook',
            'client_id' => 'fb_client',
            'client_secret' => 'fb_secret',
            'is_active' => true,
        ]);

        $tenantProviders = TenantOAuthProvider::where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->get();

        $this->assertCount(1, $tenantProviders);
        $this->assertEquals('facebook', $tenantProviders->first()->provider);
    }
}