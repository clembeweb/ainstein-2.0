<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantOAuthProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

class OAuthMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant with a user
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'subdomain' => 'test-tenant',
            'plan_type' => 'pro',
            'status' => 'active',
            'tokens_monthly_limit' => 100000,
            'tokens_used_current' => 0,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    public function test_tenant_can_access_oauth_settings_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tenant.settings.oauth.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.settings.oauth');
        $response->assertViewHas('googleProvider');
        $response->assertViewHas('facebookProvider');
    }

    public function test_tenant_can_configure_google_oauth()
    {
        $response = $this->actingAs($this->user)
            ->put(route('tenant.settings.oauth.google.update'), [
                'client_id' => 'test-google-client-id',
                'client_secret' => 'test-google-client-secret',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('tenant.settings.oauth.index'));
        $response->assertSessionHas('success');

        // Verify in database
        $provider = TenantOAuthProvider::where('tenant_id', $this->tenant->id)
            ->where('provider', 'google')
            ->first();

        $this->assertNotNull($provider);
        $this->assertEquals('test-google-client-id', $provider->client_id);
        $this->assertEquals('test-google-client-secret', $provider->client_secret);
        $this->assertTrue($provider->is_active);
    }

    public function test_tenant_can_configure_facebook_oauth()
    {
        $response = $this->actingAs($this->user)
            ->put(route('tenant.settings.oauth.facebook.update'), [
                'client_id' => 'test-facebook-app-id',
                'client_secret' => 'test-facebook-app-secret',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('tenant.settings.oauth.index'));
        $response->assertSessionHas('success');

        // Verify in database
        $provider = TenantOAuthProvider::where('tenant_id', $this->tenant->id)
            ->where('provider', 'facebook')
            ->first();

        $this->assertNotNull($provider);
        $this->assertEquals('test-facebook-app-id', $provider->client_id);
        $this->assertEquals('test-facebook-app-secret', $provider->client_secret);
        $this->assertTrue($provider->is_active);
    }

    public function test_oauth_credentials_are_encrypted()
    {
        $provider = TenantOAuthProvider::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'google',
            'client_id' => 'plain-text-client-id',
            'client_secret' => 'plain-text-secret',
            'is_active' => true,
        ]);

        // Get raw values from database
        $raw = \DB::table('tenant_oauth_providers')
            ->where('id', $provider->id)
            ->first();

        // Verify encryption in database
        $this->assertNotEquals('plain-text-client-id', $raw->client_id);
        $this->assertNotEquals('plain-text-secret', $raw->client_secret);

        // Verify decryption works
        $this->assertEquals('plain-text-client-id', $provider->fresh()->client_id);
        $this->assertEquals('plain-text-secret', $provider->fresh()->client_secret);
    }

    public function test_tenant_cannot_enable_provider_without_configuration()
    {
        $provider = TenantOAuthProvider::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'google',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tenant.settings.oauth.toggle', 'google'));

        $response->assertRedirect(route('tenant.settings.oauth.index'));
        $response->assertSessionHas('error');

        // Verify still disabled
        $this->assertFalse($provider->fresh()->is_active);
    }

    public function test_tenant_can_clear_oauth_configuration()
    {
        $provider = TenantOAuthProvider::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'google',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-secret',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tenant.settings.oauth.clear', 'google'));

        $response->assertRedirect(route('tenant.settings.oauth.index'));
        $response->assertSessionHas('success');

        $provider->refresh();
        $this->assertNull($provider->client_id);
        $this->assertNull($provider->client_secret);
        $this->assertFalse($provider->is_active);
    }

    public function test_each_tenant_has_separate_oauth_configurations()
    {
        // Create second tenant
        $tenant2 = Tenant::create([
            'name' => 'Second Tenant',
            'subdomain' => 'second-tenant',
            'plan_type' => 'basic',
            'status' => 'active',
            'tokens_monthly_limit' => 50000,
            'tokens_used_current' => 0,
        ]);

        // Configure OAuth for first tenant
        TenantOAuthProvider::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'google',
            'client_id' => 'tenant1-google-id',
            'client_secret' => 'tenant1-google-secret',
            'is_active' => true,
        ]);

        // Configure OAuth for second tenant
        TenantOAuthProvider::create([
            'tenant_id' => $tenant2->id,
            'provider' => 'google',
            'client_id' => 'tenant2-google-id',
            'client_secret' => 'tenant2-google-secret',
            'is_active' => true,
        ]);

        // Verify each tenant has its own configuration
        $provider1 = TenantOAuthProvider::where('tenant_id', $this->tenant->id)
            ->where('provider', 'google')
            ->first();

        $provider2 = TenantOAuthProvider::where('tenant_id', $tenant2->id)
            ->where('provider', 'google')
            ->first();

        $this->assertEquals('tenant1-google-id', $provider1->client_id);
        $this->assertEquals('tenant2-google-id', $provider2->client_id);
    }

    public function test_oauth_service_returns_available_providers()
    {
        // Configure Google for tenant
        TenantOAuthProvider::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'google',
            'client_id' => 'google-client-id',
            'client_secret' => 'google-secret',
            'is_active' => true,
        ]);

        $service = app(\App\Services\TenantOAuthService::class);
        $providers = $service->getAvailableProviders($this->tenant->id);

        $this->assertContains('google', $providers);
        $this->assertNotContains('facebook', $providers);
    }

    public function test_social_login_redirect_fails_without_configuration()
    {
        $response = $this->get(route('social.redirect', 'google'));

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }
}