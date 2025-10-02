<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $superAdmin;
    protected User $tenantAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant One']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant Two']);

        // Create test users
        $this->superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->tenantAdmin = User::factory()->admin()->create([
            'name' => 'Tenant Admin',
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'role' => 'user',
            'tenant_id' => $this->tenant1->id,
        ]);
    }

    /** @test */
    public function test_super_admin_can_list_all_tenants(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/tenants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'domain',
                        'subdomain',
                        'plan_type',
                        'status',
                        'users_count',
                        'pages_count',
                        'content_generations_count',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertCount(2, $responseData);
    }

    /** @test */
    public function test_tenant_admin_can_only_see_own_tenant(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $response = $this->getJson('/api/v1/tenants');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals($this->tenant1->id, $responseData[0]['id']);
        $this->assertEquals('Tenant One', $responseData[0]['name']);
    }

    /** @test */
    public function test_regular_user_can_only_see_own_tenant(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/v1/tenants');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals($this->tenant1->id, $responseData[0]['id']);
    }

    /** @test */
    public function test_super_admin_can_create_tenant(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'subdomain' => 'newtenant',
            'plan_type' => 'pro',
            'tokens_monthly_limit' => 50000,
        ];

        $response = $this->postJson('/api/v1/tenants', $tenantData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'subdomain',
                    'plan_type',
                    'tokens_monthly_limit',
                    'status',
                ]
            ])
            ->assertJson([
                'message' => 'Tenant created successfully',
                'data' => [
                    'name' => 'New Tenant',
                    'domain' => 'newtenant.example.com',
                    'plan_type' => 'pro',
                ]
            ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'subdomain' => 'newtenant',
        ]);
    }

    /** @test */
    public function test_regular_user_cannot_create_tenant(): void
    {
        Sanctum::actingAs($this->regularUser);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'subdomain' => 'newtenant',
        ];

        $response = $this->postJson('/api/v1/tenants', $tenantData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. Only super admins can create tenants.'
            ]);
    }

    /** @test */
    public function test_tenant_admin_cannot_create_tenant(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $tenantData = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'subdomain' => 'newtenant',
        ];

        $response = $this->postJson('/api/v1/tenants', $tenantData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. Only super admins can create tenants.'
            ]);
    }

    /** @test */
    public function test_tenant_admin_can_update_own_tenant(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $updateData = [
            'name' => 'Updated Tenant Name',
            'theme_config' => [
                'primary_color' => '#ff0000',
                'secondary_color' => '#00ff00',
            ]
        ];

        $response = $this->putJson("/api/v1/tenants/{$this->tenant1->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'theme_config',
                ]
            ])
            ->assertJson([
                'message' => 'Tenant updated successfully',
                'data' => [
                    'name' => 'Updated Tenant Name',
                ]
            ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant1->id,
            'name' => 'Updated Tenant Name',
        ]);
    }

    /** @test */
    public function test_tenant_admin_cannot_update_other_tenant(): void
    {
        Sanctum::actingAs($this->tenantAdmin);

        $updateData = ['name' => 'Hacked Tenant'];

        $response = $this->putJson("/api/v1/tenants/{$this->tenant2->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. You can only update your own tenant.'
            ]);
    }

    /** @test */
    public function test_regular_user_cannot_update_tenant(): void
    {
        Sanctum::actingAs($this->regularUser);

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/v1/tenants/{$this->tenant1->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. You can only update your own tenant.'
            ]);
    }

    /** @test */
    public function test_super_admin_can_update_any_tenant(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $updateData = [
            'name' => 'Super Admin Updated',
            'status' => 'suspended',
        ];

        $response = $this->putJson("/api/v1/tenants/{$this->tenant2->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tenant updated successfully',
                'data' => [
                    'name' => 'Super Admin Updated',
                    'status' => 'suspended',
                ]
            ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant2->id,
            'name' => 'Super Admin Updated',
            'status' => 'suspended',
        ]);
    }

    /** @test */
    public function test_super_admin_can_delete_tenant(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // Create a tenant with no active users
        $tenant = Tenant::factory()->create();

        $response = $this->deleteJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tenant deleted successfully'
            ]);

        $this->assertDatabaseMissing('tenants', [
            'id' => $tenant->id,
        ]);
    }

    /** @test */
    public function test_regular_user_cannot_delete_tenant(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->deleteJson("/api/v1/tenants/{$this->tenant2->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. Only super admins can delete tenants.'
            ]);
    }

    /** @test */
    public function test_cannot_delete_tenant_with_active_users(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->deleteJson("/api/v1/tenants/{$this->tenant1->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete tenant with active users. Please deactivate all users first.'
            ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant1->id,
        ]);
    }

    /** @test */
    public function test_can_delete_tenant_after_deactivating_users(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // Deactivate all users for this tenant
        $this->tenant1->users()->update(['is_active' => false]);

        $response = $this->deleteJson("/api/v1/tenants/{$this->tenant1->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tenant deleted successfully'
            ]);

        $this->assertDatabaseMissing('tenants', [
            'id' => $this->tenant1->id,
        ]);
    }

    /** @test */
    public function test_tenant_isolation_is_enforced(): void
    {
        // Create pages for each tenant
        $page1 = Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'url_path' => '/tenant1-page',
        ]);

        $page2 = Page::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'url_path' => '/tenant2-page',
        ]);

        // Create a user for tenant2
        $tenant2User = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
        ]);

        // Test that tenant1 admin can only see tenant1 data
        Sanctum::actingAs($this->tenantAdmin);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant1->id}");
        $response->assertStatus(200);

        $tenantData = $response->json('data');
        $this->assertEquals($this->tenant1->id, $tenantData['id']);

        // Test that tenant1 admin cannot access tenant2 data
        $response = $this->getJson("/api/v1/tenants/{$this->tenant2->id}");
        $response->assertStatus(403);

        // Test that super admin can access both tenants
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant1->id}");
        $response->assertStatus(200);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant2->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function test_tenant_show_includes_related_counts(): void
    {
        // Create related data for tenant1
        User::factory(3)->create(['tenant_id' => $this->tenant1->id]);
        Page::factory(5)->create(['tenant_id' => $this->tenant1->id]);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant1->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'users',
                    'pages',
                    'prompts',
                    'content_generations_count',
                    'cms_connections_count',
                ]
            ]);

        $tenantData = $response->json('data');
        $this->assertCount(6, $tenantData['users']); // 3 new + 3 created in setUp
        $this->assertCount(5, $tenantData['pages']);
    }

    /** @test */
    public function test_tenant_list_pagination_works(): void
    {
        // Create additional tenants
        Tenant::factory(20)->create();

        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/tenants?per_page=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ]);

        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(5, $meta['per_page']);
        $this->assertEquals(22, $meta['total']); // 20 new + 2 from setUp
        $this->assertGreaterThan(1, $meta['last_page']);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_tenant_endpoints(): void
    {
        $endpoints = [
            ['method' => 'get', 'uri' => '/api/v1/tenants'],
            ['method' => 'post', 'uri' => '/api/v1/tenants'],
            ['method' => 'get', 'uri' => "/api/v1/tenants/{$this->tenant1->id}"],
            ['method' => 'put', 'uri' => "/api/v1/tenants/{$this->tenant1->id}"],
            ['method' => 'delete', 'uri' => "/api/v1/tenants/{$this->tenant1->id}"],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['uri']);
            $response->assertStatus(401);
        }
    }
}
