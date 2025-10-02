<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentGeneration>
 */
class ContentGenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prompt_type' => fake()->randomElement(['meta_title', 'meta_description', 'content', 'outline']),
            'generated_content' => fake()->paragraph(5),
            'meta_title' => fake()->sentence(6, true),
            'meta_description' => fake()->paragraph(2),
            'tokens_used' => fake()->numberBetween(100, 2000),
            'ai_model' => fake()->randomElement(['gpt-4', 'gpt-3.5-turbo', 'claude-3', 'gemini-pro']),
            'status' => 'completed',
            'error' => null,
            'published_at' => null,
            'page_id' => Page::factory(),
            'tenant_id' => Tenant::factory(),
        ];
    }

    /**
     * Create a content generation with pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'generated_content' => null,
            'meta_title' => null,
            'meta_description' => null,
            'tokens_used' => 0,
        ]);
    }

    /**
     * Create a content generation with processing status.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'generated_content' => null,
            'meta_title' => null,
            'meta_description' => null,
            'tokens_used' => 0,
        ]);
    }

    /**
     * Create a content generation with failed status.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'generated_content' => null,
            'meta_title' => null,
            'meta_description' => null,
            'tokens_used' => 0,
            'error' => 'API request failed: Rate limit exceeded',
        ]);
    }

    /**
     * Create a published content generation.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a content generation for a specific page.
     */
    public function forPage(Page $page): static
    {
        return $this->state(fn (array $attributes) => [
            'page_id' => $page->id,
            'tenant_id' => $page->tenant_id,
        ]);
    }

    /**
     * Create a content generation for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Create a high token usage content generation.
     */
    public function highTokenUsage(): static
    {
        return $this->state(fn (array $attributes) => [
            'tokens_used' => fake()->numberBetween(5000, 10000),
        ]);
    }
}