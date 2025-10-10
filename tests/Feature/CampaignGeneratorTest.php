<?php

namespace Tests\Feature;

use App\Models\AdvCampaign;
use App\Models\AdvGeneratedAsset;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tools\CampaignAssetsGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class CampaignGeneratorTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;
    protected AdvCampaign $campaign1;
    protected AdvCampaign $campaign2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants with sufficient tokens
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Tenant One',
            'tokens_monthly_limit' => 100000,
            'tokens_used_current' => 1000,
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Tenant Two',
            'tokens_monthly_limit' => 100000,
            'tokens_used_current' => 500,
        ]);

        // Create test users
        $this->user1 = User::factory()->create([
            'name' => 'User One',
            'tenant_id' => $this->tenant1->id,
            'role' => 'user',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'User Two',
            'tenant_id' => $this->tenant2->id,
            'role' => 'user',
        ]);

        // Create test campaigns
        $this->campaign1 = AdvCampaign::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Campaign One',
            'type' => 'rsa',
            'info' => 'Test business description',
            'keywords' => 'keyword1, keyword2, keyword3',
            'language' => 'it',
        ]);

        $this->campaign2 = AdvCampaign::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Campaign Two',
            'type' => 'pmax',
            'info' => 'Another business',
            'keywords' => 'pmax, campaign',
            'language' => 'en',
        ]);
    }

    /** @test */
    public function test_authenticated_user_can_view_campaigns_index(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('tenant.campaigns.index'));

        $response->assertStatus(200)
            ->assertViewIs('tenant.campaigns.index')
            ->assertViewHas('campaigns')
            ->assertSee('Campaign One');
    }

    /** @test */
    public function test_user_can_only_see_own_tenant_campaigns(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('tenant.campaigns.index'));

        $response->assertStatus(200)
            ->assertSee('Campaign One')
            ->assertDontSee('Campaign Two');
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_campaigns(): void
    {
        $response = $this->get(route('tenant.campaigns.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function test_user_can_view_create_campaign_form(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('tenant.campaigns.create'));

        $response->assertStatus(200)
            ->assertViewIs('tenant.campaigns.create')
            ->assertSee('Nuova Campaign');
    }

    /** @test */
    public function test_user_can_create_rsa_campaign(): void
    {
        $this->actingAs($this->user1);

        // Mock the CampaignAssetsGenerator service
        $mockService = Mockery::mock(CampaignAssetsGenerator::class);
        $mockAsset = new AdvGeneratedAsset([
            'titles' => ['Title 1', 'Title 2', 'Title 3'],
            'descriptions' => ['Description 1', 'Description 2'],
            'tokens_used' => 500,
        ]);

        $mockService->shouldReceive('generate')
            ->once()
            ->andReturn($mockAsset);

        $this->app->instance(CampaignAssetsGenerator::class, $mockService);

        $campaignData = [
            'campaign_name' => 'New RSA Campaign',
            'campaign_type' => 'RSA',
            'business_description' => 'We sell luxury watches',
            'target_keywords' => 'watches, luxury, swiss',
            'url' => 'https://example.com',
        ];

        $response = $this->post(route('tenant.campaigns.store'), $campaignData);

        $response->assertRedirect();

        $this->assertDatabaseHas('adv_campaigns', [
            'tenant_id' => $this->tenant1->id,
            'name' => 'New RSA Campaign',
            'type' => 'rsa',
            'info' => 'We sell luxury watches',
            'keywords' => 'watches, luxury, swiss',
        ]);
    }

    /** @test */
    public function test_user_can_create_pmax_campaign(): void
    {
        $this->actingAs($this->user1);

        // Mock the service
        $mockService = Mockery::mock(CampaignAssetsGenerator::class);
        $mockAsset = new AdvGeneratedAsset([
            'titles' => ['Short 1', 'Short 2'],
            'long_titles' => ['Long Title 1', 'Long Title 2'],
            'descriptions' => ['Desc 1', 'Desc 2'],
            'tokens_used' => 800,
        ]);

        $mockService->shouldReceive('generate')
            ->once()
            ->andReturn($mockAsset);

        $this->app->instance(CampaignAssetsGenerator::class, $mockService);

        $campaignData = [
            'campaign_name' => 'New PMAX Campaign',
            'campaign_type' => 'PMAX',
            'business_description' => 'We offer premium services',
            'target_keywords' => 'services, premium, quality',
        ];

        $response = $this->post(route('tenant.campaigns.store'), $campaignData);

        $response->assertRedirect();

        $this->assertDatabaseHas('adv_campaigns', [
            'tenant_id' => $this->tenant1->id,
            'name' => 'New PMAX Campaign',
            'type' => 'pmax',
        ]);
    }

    /** @test */
    public function test_campaign_creation_requires_all_fields(): void
    {
        $this->actingAs($this->user1);

        $response = $this->post(route('tenant.campaigns.store'), []);

        $response->assertSessionHasErrors([
            'campaign_name',
            'campaign_type',
            'business_description',
            'target_keywords',
        ]);
    }

    /** @test */
    public function test_campaign_type_must_be_valid(): void
    {
        $this->actingAs($this->user1);

        $campaignData = [
            'campaign_name' => 'Test',
            'campaign_type' => 'INVALID',
            'business_description' => 'Test description',
            'target_keywords' => 'test, keywords',
        ];

        $response = $this->post(route('tenant.campaigns.store'), $campaignData);

        $response->assertSessionHasErrors('campaign_type');
    }

    /** @test */
    public function test_user_can_view_campaign_details(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('tenant.campaigns.show', $this->campaign1->id));

        $response->assertStatus(200)
            ->assertViewIs('tenant.campaigns.show')
            ->assertViewHas('campaign')
            ->assertSee('Campaign One')
            ->assertSee('Test business description');
    }

    /** @test */
    public function test_user_cannot_view_other_tenant_campaign(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('tenant.campaigns.show', $this->campaign2->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function test_user_can_view_edit_campaign_form(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('tenant.campaigns.edit', $this->campaign1->id));

        $response->assertStatus(200)
            ->assertViewIs('tenant.campaigns.edit')
            ->assertViewHas('campaign')
            ->assertSee('Campaign One');
    }

    /** @test */
    public function test_user_can_update_campaign(): void
    {
        $this->actingAs($this->user1);

        $updateData = [
            'campaign_name' => 'Updated Campaign Name',
            'business_description' => 'Updated description',
            'target_keywords' => 'updated, keywords',
            'url' => 'https://updated.com',
            'language' => 'en',
        ];

        $response = $this->put(route('tenant.campaigns.update', $this->campaign1->id), $updateData);

        $response->assertRedirect(route('tenant.campaigns.show', $this->campaign1->id));

        $this->assertDatabaseHas('adv_campaigns', [
            'id' => $this->campaign1->id,
            'name' => 'Updated Campaign Name',
            'info' => 'Updated description',
            'keywords' => 'updated, keywords',
            'url' => 'https://updated.com',
            'language' => 'en',
        ]);
    }

    /** @test */
    public function test_user_cannot_update_other_tenant_campaign(): void
    {
        $this->actingAs($this->user1);

        $updateData = [
            'campaign_name' => 'Hacked Campaign',
            'business_description' => 'Hacked',
            'target_keywords' => 'hacked',
        ];

        $response = $this->put(route('tenant.campaigns.update', $this->campaign2->id), $updateData);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('adv_campaigns', [
            'id' => $this->campaign2->id,
            'name' => 'Hacked Campaign',
        ]);
    }

    /** @test */
    public function test_user_can_delete_campaign(): void
    {
        $this->actingAs($this->user1);

        $response = $this->delete(route('tenant.campaigns.destroy', $this->campaign1->id));

        $response->assertRedirect(route('tenant.campaigns.index'));

        $this->assertDatabaseMissing('adv_campaigns', [
            'id' => $this->campaign1->id,
        ]);
    }

    /** @test */
    public function test_user_cannot_delete_other_tenant_campaign(): void
    {
        $this->actingAs($this->user1);

        $response = $this->delete(route('tenant.campaigns.destroy', $this->campaign2->id));

        $response->assertStatus(404);

        $this->assertDatabaseHas('adv_campaigns', [
            'id' => $this->campaign2->id,
        ]);
    }

    /** @test */
    public function test_deleting_campaign_cascades_to_assets(): void
    {
        $this->actingAs($this->user1);

        // Create assets for the campaign
        $asset = AdvGeneratedAsset::factory()->create([
            'campaign_id' => $this->campaign1->id,
            'titles' => ['Title 1', 'Title 2'],
            'descriptions' => ['Desc 1'],
        ]);

        $this->assertDatabaseHas('adv_generated_assets', [
            'campaign_id' => $this->campaign1->id,
        ]);

        $response = $this->delete(route('tenant.campaigns.destroy', $this->campaign1->id));

        $response->assertRedirect();

        // Verify campaign and assets are deleted
        $this->assertDatabaseMissing('adv_campaigns', [
            'id' => $this->campaign1->id,
        ]);

        $this->assertDatabaseMissing('adv_generated_assets', [
            'campaign_id' => $this->campaign1->id,
        ]);
    }

    /** @test */
    public function test_user_can_regenerate_campaign_assets(): void
    {
        $this->actingAs($this->user1);

        // Create existing assets
        $oldAsset = AdvGeneratedAsset::factory()->create([
            'campaign_id' => $this->campaign1->id,
            'titles' => ['Old Title 1'],
            'descriptions' => ['Old Desc 1'],
        ]);

        // Mock the service for regeneration
        $mockService = Mockery::mock(CampaignAssetsGenerator::class);
        $newMockAsset = new AdvGeneratedAsset([
            'titles' => ['New Title 1', 'New Title 2'],
            'descriptions' => ['New Desc 1', 'New Desc 2'],
            'tokens_used' => 600,
        ]);

        $mockService->shouldReceive('generate')
            ->once()
            ->andReturn($newMockAsset);

        $this->app->instance(CampaignAssetsGenerator::class, $mockService);

        $response = $this->post(route('tenant.campaigns.regenerate', $this->campaign1->id));

        $response->assertRedirect(route('tenant.campaigns.show', $this->campaign1->id));
    }

    /** @test */
    public function test_user_cannot_regenerate_other_tenant_campaign(): void
    {
        $this->actingAs($this->user1);

        $response = $this->post(route('tenant.campaigns.regenerate', $this->campaign2->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function test_user_can_export_campaign_as_csv(): void
    {
        $this->actingAs($this->user1);

        // Create assets for the campaign
        AdvGeneratedAsset::factory()->create([
            'campaign_id' => $this->campaign1->id,
            'titles' => ['Title 1', 'Title 2'],
            'descriptions' => ['Description 1'],
        ]);

        $response = $this->get(route('tenant.campaigns.export', [
            'id' => $this->campaign1->id,
            'format' => 'csv'
        ]));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition');
    }

    /** @test */
    public function test_user_can_export_campaign_as_google_ads_format(): void
    {
        $this->actingAs($this->user1);

        // Create assets for the campaign
        AdvGeneratedAsset::factory()->create([
            'campaign_id' => $this->campaign1->id,
            'titles' => ['Title 1', 'Title 2', 'Title 3'],
            'descriptions' => ['Description 1', 'Description 2'],
        ]);

        $response = $this->get(route('tenant.campaigns.export', [
            'id' => $this->campaign1->id,
            'format' => 'google-ads'
        ]));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function test_cannot_export_campaign_without_assets(): void
    {
        $this->actingAs($this->user1);

        // Campaign1 has no assets by default

        $response = $this->get(route('tenant.campaigns.export', [
            'id' => $this->campaign1->id,
            'format' => 'csv'
        ]));

        $response->assertRedirect(route('tenant.campaigns.show', $this->campaign1->id))
            ->assertSessionHas('warning');
    }

    /** @test */
    public function test_user_cannot_export_other_tenant_campaign(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('tenant.campaigns.export', [
            'id' => $this->campaign2->id,
            'format' => 'csv'
        ]));

        $response->assertStatus(404);
    }

    /** @test */
    public function test_campaign_filters_work_correctly(): void
    {
        $this->actingAs($this->user1);

        // Create additional campaigns
        AdvCampaign::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'RSA Campaign 2',
            'type' => 'rsa',
        ]);

        AdvCampaign::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'PMAX Campaign 1',
            'type' => 'pmax',
        ]);

        // Filter by RSA type
        $response = $this->get(route('tenant.campaigns.index', ['campaign_type' => 'RSA']));

        $response->assertStatus(200)
            ->assertSee('RSA Campaign')
            ->assertDontSee('PMAX Campaign');

        // Filter by PMAX type
        $response = $this->get(route('tenant.campaigns.index', ['campaign_type' => 'PMAX']));

        $response->assertStatus(200)
            ->assertSee('PMAX Campaign')
            ->assertDontSee('RSA Campaign 2');
    }

    /** @test */
    public function test_campaign_list_is_paginated(): void
    {
        $this->actingAs($this->user1);

        // Create 25 campaigns for tenant1
        AdvCampaign::factory(25)->create([
            'tenant_id' => $this->tenant1->id,
        ]);

        $response = $this->get(route('tenant.campaigns.index'));

        $response->assertStatus(200)
            ->assertViewHas('campaigns');

        $campaigns = $response->viewData('campaigns');
        $this->assertLessThanOrEqual(20, $campaigns->count());
        $this->assertTrue($campaigns->hasPages());
    }

    /** @test */
    public function test_tenant_isolation_is_enforced_in_all_operations(): void
    {
        $this->actingAs($this->user1);

        // Try to access tenant2's campaign
        $endpoints = [
            ['method' => 'get', 'route' => 'tenant.campaigns.show', 'id' => $this->campaign2->id],
            ['method' => 'get', 'route' => 'tenant.campaigns.edit', 'id' => $this->campaign2->id],
            ['method' => 'put', 'route' => 'tenant.campaigns.update', 'id' => $this->campaign2->id],
            ['method' => 'delete', 'route' => 'tenant.campaigns.destroy', 'id' => $this->campaign2->id],
            ['method' => 'post', 'route' => 'tenant.campaigns.regenerate', 'id' => $this->campaign2->id],
            ['method' => 'get', 'route' => 'tenant.campaigns.export', 'id' => $this->campaign2->id, 'format' => 'csv'],
        ];

        foreach ($endpoints as $endpoint) {
            $params = ['id' => $endpoint['id']];
            if (isset($endpoint['format'])) {
                $params['format'] = $endpoint['format'];
            }

            $response = $this->{$endpoint['method']}(route($endpoint['route'], $params), []);

            // Should get 404 (not found) because of tenant scoping
            $response->assertStatus(404);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
