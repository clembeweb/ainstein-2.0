<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url_path' => '/' . fake()->slug(),
            'keyword' => fake()->words(2, true),
            'category' => fake()->randomElement(['blog', 'product', 'landing', 'about']),
            'language' => 'en',
            'cms_type' => 'wordpress',
            'cms_page_id' => fake()->randomNumber(4),
            'status' => 'active',
            'priority' => fake()->numberBetween(1, 10),
            'metadata' => [
                'title' => fake()->sentence(),
                'description' => fake()->paragraph(),
                'author' => fake()->name(),
                'tags' => fake()->words(3),
            ],
            'last_synced' => fake()->dateTimeBetween('-1 week', 'now'),
            'tenant_id' => Tenant::factory(),
        ];
    }

    /**
     * Create a page with draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Create a page with published status.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    /**
     * Create a page with archived status.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Create a page with high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(8, 10),
        ]);
    }

    /**
     * Create a page that hasn't been synced recently.
     */
    public function outdated(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_synced' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }

    /**
     * Create a page for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}