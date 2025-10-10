<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke Test for Campaign Generator
 *
 * This is a minimal test that bypasses SQLite transaction issues
 * to verify basic route accessibility and authentication.
 *
 * For comprehensive testing, see CAMPAIGN_GENERATOR_TEST_PLAN.md
 */
class CampaignGeneratorSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_access_campaign_index_route()
    {
        // Create a tenant and user without transactions
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'subdomain' => 'test',
            'plan_type' => 'starter',
            'tokens_monthly_limit' => 10000,
            'tokens_used_current' => 0,
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'user',
            'is_active' => true,
            'email_verified' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('tenant.campaigns.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.campaigns.index');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_campaign_routes()
    {
        $response = $this->get(route('tenant.campaigns.index'));

        $response->assertRedirect(route('login'));
    }
}
