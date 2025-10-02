<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Page;
use App\Models\ContentGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $superAdmin;
    protected User $tenantUser1;
    protected User $tenantUser2;
    protected Page $page1;
    protected Page $page2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant One']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant Two']);

        // Create test users
        $this->superAdmin = User::factory()->superAdmin()->create([
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->tenantUser1 = User::factory()->create([
            'role' => 'user',
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->tenantUser2 = User::factory()->create([
            'role' => 'user',
            'tenant_id' => $this->tenant2->id,
        ]);

        // Create test pages
        $this->page1 = Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'url_path' => '/test-page-1',
            'keyword' => 'test keyword 1',
            'status' => 'active',
        ]);

        $this->page2 = Page::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'url_path' => '/test-page-2',
            'keyword' => 'test keyword 2',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_user_can_list_pages_for_own_tenant(): void
    {
        // Create additional pages for tenant1
        Page::factory(3)->create(['tenant_id' => $this->tenant1->id]);

        Sanctum::actingAs($this->tenantUser1);

        $response = $this->getJson('/api/v1/pages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'url_path',
                        'keyword',
                        'category',
                        'language',
                        'status',
                        'priority',
                        'tenant',
                        'generations_count',
                        'generations',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ]);

        $pages = $response->json('data');
        $this->assertCount(4, $pages); // 1 from setUp + 3 created above

        // Verify all pages belong to tenant1
        foreach ($pages as $page) {
            $this->assertEquals($this->tenant1->id, $page['tenant']['id']);
        }
    }

    /** @test */
    public function test_user_cannot_see_other_tenant_pages(): void
    {
        Sanctum::actingAs($this->tenantUser1);

        $response = $this->getJson('/api/v1/pages');

        $pages = $response->json('data');

        // Should only see tenant1 pages, not tenant2 pages
        $pageIds = collect($pages)->pluck('id')->toArray();
        $this->assertContains($this->page1->id, $pageIds);
        $this->assertNotContains($this->page2->id, $pageIds);
    }

    /** @test */
    public function test_super_admin_can_view_pages_from_specific_tenant(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // Super admin can view pages from tenant2 by specifying tenant_id
        $response = $this->getJson("/api/v1/pages?tenant_id={$this->tenant2->id}");

        $response->assertStatus(200);

        $pages = $response->json('data');
        $this->assertCount(1, $pages);
        $this->assertEquals($this->tenant2->id, $pages[0]['tenant']['id']);
    }

    /** @test */
    public function test_user_can_create_page(): void
    {
        Sanctum::actingAs($this->tenantUser1);

        $pageData = [
            'url_path' => '/new-test-page',
            'keyword' => 'new test keyword',
            'category' => 'blog',
            'language' => 'en',
            'cms_type' => 'wordpress',
            'cms_page_id' => '12345',
            'status' => 'active',
            'priority' => 5,
            'metadata' => [
                'title' => 'New Test Page',
                'description' => 'This is a test page',
                'tags' => ['test', 'new'],
            ],
        ];

        $response = $this->postJson('/api/v1/pages', $pageData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'url_path',
                    'keyword',
                    'category',
                    'status',
                    'tenant',
                ]
            ])
            ->assertJson([
                'message' => 'Page created successfully',
                'data' => [
                    'url_path' => '/new-test-page',
                    'keyword' => 'new test keyword',
                    'category' => 'blog',
                ]
            ]);

        $this->assertDatabaseHas('pages', [
            'url_path' => '/new-test-page',
            'keyword' => 'new test keyword',
            'tenant_id' => $this->tenant1->id,
        ]);
    }

    /** @test */
    public function test_page_is_automatically_assigned_to_users_tenant(): void
    {
        Sanctum::actingAs($this->tenantUser2);

        $pageData = [
            'url_path' => '/tenant2-page',
            'keyword' => 'tenant2 keyword',
            'category' => 'product',
        ];

        $response = $this->postJson('/api/v1/pages', $pageData);

        $response->assertStatus(201);

        $page = $response->json('data');
        $this->assertEquals($this->tenant2->id, $page['tenant']['id']);

        $this->assertDatabaseHas('pages', [
            'url_path' => '/tenant2-page',
            'tenant_id' => $this->tenant2->id,
        ]);
    }

    /** @test */
    public function test_user_can_update_own_page(): void
    {
        Sanctum::actingAs($this->tenantUser1);

        $updateData = [
            'url_path' => '/updated-page',
            'keyword' => 'updated keyword',
            'status' => 'draft',
            'priority' => 8,
        ];

        $response = $this->putJson("/api/v1/pages/{$this->page1->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Page updated successfully',
                'data' => [
                    'url_path' => '/updated-page',
                    'keyword' => 'updated keyword',
                    'status' => 'draft',
                    'priority' => 8,
                ]
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page1->id,
            'url_path' => '/updated-page',
            'keyword' => 'updated keyword',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function test_user_cannot_update_other_tenant_page(): void
    {
        Sanctum::actingAs($this->tenantUser1);

        $updateData = ['url_path' => '/hacked-page'];

        $response = $this->putJson("/api/v1/pages/{$this->page2->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. You can only update pages from your tenant.'
            ]);

        // Verify page was not updated
        $this->assertDatabaseHas('pages', [
            'id' => $this->page2->id,
            'url_path' => '/test-page-2', // Original value
        ]);
    }

    /** @test */
    public function test_super_admin_can_update_any_page(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $updateData = [
            'url_path' => '/super-admin-updated',
            'status' => 'archived',
        ];

        $response = $this->putJson("/api/v1/pages/{$this->page2->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Page updated successfully',
                'data' => [
                    'url_path' => '/super-admin-updated',
                    'status' => 'archived',
                ]
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page2->id,
            'url_path' => '/super-admin-updated',
            'status' => 'archived',
        ]);
    }

    /** @test */
    public function test_user_can_delete_own_page(): void
    {
        Sanctum::actingAs($this->tenantUser1);

        $response = $this->deleteJson("/api/v1/pages/{$this->page1->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Page deleted successfully'
            ]);

        $this->assertDatabaseMissing('pages', [
            'id' => $this->page1->id,
        ]);
    }

    /** @test */
    public function test_user_cannot_delete_other_tenant_page(): void
    {
        Sanctum::actingAs($this->tenantUser1);

        $response = $this->deleteJson("/api/v1/pages/{$this->page2->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. You can only delete pages from your tenant.'
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page2->id,
        ]);
    }

    /** @test */
    public function test_cannot_delete_page_with_content_generations(): void
    {
        // Create a content generation for the page
        ContentGeneration::factory()->create([
            'page_id' => $this->page1->id,
            'tenant_id' => $this->tenant1->id,
        ]);

        Sanctum::actingAs($this->tenantUser1);

        $response = $this->deleteJson("/api/v1/pages/{$this->page1->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete page with existing content generations. Please delete the generations first.'
            ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page1->id,
        ]);
    }

    /** @test */
    public function test_page_validation_works(): void
    {
        Sanctum::actingAs($this->tenantUser1);

        $testCases = [
            // Missing required fields
            [
                'data' => [],
                'expected_errors' => ['url_path', 'keyword'],
            ],
            // Invalid URL path format
            [
                'data' => [
                    'url_path' => 'invalid-path-without-slash',
                    'keyword' => 'test',
                ],
                'expected_errors' => ['url_path'],
            ],
            // Invalid status
            [
                'data' => [
                    'url_path' => '/valid-path',
                    'keyword' => 'test',
                    'status' => 'invalid_status',
                ],
                'expected_errors' => ['status'],
            ],
            // Invalid priority range
            [
                'data' => [
                    'url_path' => '/valid-path',
                    'keyword' => 'test',
                    'priority' => 15, // Assuming max is 10
                ],
                'expected_errors' => ['priority'],
            ],
        ];

        foreach ($testCases as $testCase) {
            $response = $this->postJson('/api/v1/pages', $testCase['data']);

            $response->assertStatus(422);

            foreach ($testCase['expected_errors'] as $field) {
                $response->assertJsonValidationErrors($field);
            }
        }
    }

    /** @test */
    public function test_pagination_works(): void
    {
        // Create additional pages
        Page::factory(20)->create(['tenant_id' => $this->tenant1->id]);

        Sanctum::actingAs($this->tenantUser1);

        $response = $this->getJson('/api/v1/pages?per_page=5');

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
        $this->assertEquals(21, $meta['total']); // 20 new + 1 from setUp
        $this->assertGreaterThan(1, $meta['last_page']);

        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    /** @test */
    public function test_page_filtering_works(): void
    {
        // Create pages with different attributes for filtering
        Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'status' => 'draft',
            'category' => 'blog',
            'language' => 'es',
        ]);

        Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'status' => 'published',
            'category' => 'product',
            'language' => 'fr',
        ]);

        Sanctum::actingAs($this->tenantUser1);

        // Test status filter
        $response = $this->getJson('/api/v1/pages?status=draft');
        $pages = $response->json('data');
        foreach ($pages as $page) {
            $this->assertEquals('draft', $page['status']);
        }

        // Test category filter
        $response = $this->getJson('/api/v1/pages?category=blog');
        $pages = $response->json('data');
        foreach ($pages as $page) {
            $this->assertEquals('blog', $page['category']);
        }

        // Test language filter
        $response = $this->getJson('/api/v1/pages?language=es');
        $pages = $response->json('data');
        foreach ($pages as $page) {
            $this->assertEquals('es', $page['language']);
        }
    }

    /** @test */
    public function test_page_search_works(): void
    {
        // Create pages with specific keywords for search
        Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'url_path' => '/searchable-page',
            'keyword' => 'searchable keyword',
        ]);

        Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'url_path' => '/another-page',
            'keyword' => 'different keyword',
        ]);

        Sanctum::actingAs($this->tenantUser1);

        // Search by URL path
        $response = $this->getJson('/api/v1/pages?search=searchable');
        $pages = $response->json('data');
        $this->assertGreaterThan(0, count($pages));
        $found = false;
        foreach ($pages as $page) {
            if (strpos($page['url_path'], 'searchable') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        // Search by keyword
        $response = $this->getJson('/api/v1/pages?search=different');
        $pages = $response->json('data');
        $this->assertGreaterThan(0, count($pages));
        $found = false;
        foreach ($pages as $page) {
            if (strpos($page['keyword'], 'different') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /** @test */
    public function test_page_sorting_works(): void
    {
        // Create pages with different creation dates
        $oldPage = Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'created_at' => now()->subDays(5),
            'priority' => 1,
        ]);

        $newPage = Page::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'created_at' => now()->subDays(1),
            'priority' => 10,
        ]);

        Sanctum::actingAs($this->tenantUser1);

        // Test sorting by created_at desc (default)
        $response = $this->getJson('/api/v1/pages');
        $pages = $response->json('data');
        $this->assertEquals($newPage->id, $pages[0]['id']);

        // Test sorting by created_at asc
        $response = $this->getJson('/api/v1/pages?sort_by=created_at&sort_direction=asc');
        $pages = $response->json('data');
        $this->assertEquals($oldPage->id, $pages[0]['id']);

        // Test sorting by priority desc
        $response = $this->getJson('/api/v1/pages?sort_by=priority&sort_direction=desc');
        $pages = $response->json('data');
        // Find the page with highest priority
        $highestPriority = max(array_column($pages, 'priority'));
        $this->assertEquals(10, $highestPriority);
    }

    /** @test */
    public function test_page_show_includes_generations(): void
    {
        // Create content generations for the page
        ContentGeneration::factory(3)->create([
            'page_id' => $this->page1->id,
            'tenant_id' => $this->tenant1->id,
        ]);

        Sanctum::actingAs($this->tenantUser1);

        $response = $this->getJson("/api/v1/pages/{$this->page1->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'url_path',
                    'keyword',
                    'tenant',
                    'generations' => [
                        '*' => [
                            'id',
                            'page_id',
                            'prompt_type',
                            'status',
                            'tokens_used',
                            'ai_model',
                            'created_at',
                        ]
                    ]
                ]
            ]);

        $page = $response->json('data');
        $this->assertCount(3, $page['generations']);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_page_endpoints(): void
    {
        $endpoints = [
            ['method' => 'get', 'uri' => '/api/v1/pages'],
            ['method' => 'post', 'uri' => '/api/v1/pages'],
            ['method' => 'get', 'uri' => "/api/v1/pages/{$this->page1->id}"],
            ['method' => 'put', 'uri' => "/api/v1/pages/{$this->page1->id}"],
            ['method' => 'delete', 'uri' => "/api/v1/pages/{$this->page1->id}"],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['uri']);
            $response->assertStatus(401);
        }
    }
}
