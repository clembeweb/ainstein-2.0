<?php

namespace Tests\Feature\CrewAI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Crew;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test CrewAI onboarding tours integration
 *
 * Verifies that:
 * - Tour buttons are present in the UI
 * - Tour scripts are properly loaded
 * - Tours are accessible to authenticated users
 * - Tour functionality is available in crew management pages
 */
class CrewAIToursTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Crew $crew;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test-tenant.local',
            'subdomain' => 'test-tenant',
            'plan_type' => 'starter',
            'status' => 'active',
        ]);

        // Create test user
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create test crew
        $this->crew = Crew::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Crew',
            'description' => 'Test crew for tour testing',
            'process_type' => 'sequential',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function crew_show_page_includes_launch_tour_button()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tenant.crews.show', $this->crew));

        $response->assertStatus(200);
        $response->assertSee('Show Tour', false); // false = exact match
        $response->assertSee('Launch Crew Execution', false);
    }

    /** @test */
    public function crew_show_page_includes_tour_javascript()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tenant.crews.show', $this->crew));

        $response->assertStatus(200);

        // Check for Shepherd.js tour initialization functions
        $response->assertSee('launchCrewTour', false);
        $response->assertSee('Shepherd', false);
    }

    /** @test */
    public function crew_execution_page_includes_monitor_tour()
    {
        // Create a test execution
        $execution = $this->crew->executions()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'running',
            'configuration' => json_encode(['test' => true]),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tenant.crew-executions.show', $execution));

        $response->assertStatus(200);
        $response->assertSee('Show Tour', false);
        $response->assertSee('Monitor Execution', false);
    }

    /** @test */
    public function tours_require_authentication()
    {
        // Try to access crew show page without authentication
        $response = $this->get(route('tenant.crews.show', $this->crew));

        // Should redirect to login
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function crew_index_page_loads_successfully()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tenant.crews.index'));

        $response->assertStatus(200);
        $response->assertSee($this->crew->name);
    }

    /** @test */
    public function tour_buttons_use_correct_alpine_directives()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tenant.crews.show', $this->crew));

        $response->assertStatus(200);

        // Verify Alpine.js click handlers are present
        $content = $response->getContent();
        $this->assertStringContainsString('@click', $content);
        $this->assertStringContainsString('launchCrewTour', $content);
    }

    /** @test */
    public function crew_page_includes_required_css_classes_for_tour_targeting()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tenant.crews.show', $this->crew));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Verify key elements that tours target are present
        $this->assertStringContainsString('Launch Crew', $content);
        $this->assertStringContainsString('Agents', $content);
        $this->assertStringContainsString('Tasks', $content);
    }

    /** @test */
    public function user_can_access_crew_without_errors()
    {
        // This test ensures the basic crew show route works
        // which is required for tours to function
        $response = $this->actingAs($this->user)
            ->get(route('tenant.crews.show', $this->crew));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.crews.show');
        $response->assertViewHas('crew');
    }

    /** @test */
    public function tour_javascript_files_are_compiled()
    {
        // Verify that the tour JS files exist in the compiled assets
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);

            // Check if app.js is compiled (which should include tour imports)
            $this->assertArrayHasKey('resources/js/app.js', $manifest);
        } else {
            // If no manifest, check if tour source files exist
            $this->assertFileExists(resource_path('js/tours/crew-launch-tour.js'));
            $this->assertFileExists(resource_path('js/tours/execution-monitor-tour.js'));
        }
    }

    /** @test */
    public function shepherd_library_is_included_in_npm_packages()
    {
        $packageJsonPath = base_path('package.json');

        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);

            // Check if shepherd.js is in dependencies or devDependencies
            $hasShepherd = isset($packageJson['dependencies']['shepherd.js'])
                        || isset($packageJson['devDependencies']['shepherd.js']);

            $this->assertTrue($hasShepherd, 'Shepherd.js should be in package.json dependencies');
        }
    }
}
