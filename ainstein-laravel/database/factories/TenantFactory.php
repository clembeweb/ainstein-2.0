<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'domain' => fake()->domainName(),
            'subdomain' => fake()->slug(),
            'plan_type' => 'starter',
            'tokens_monthly_limit' => 10000,
            'tokens_used_current' => 0,
            'status' => 'active',
            'theme_config' => [
                'primary_color' => fake()->hexColor(),
                'secondary_color' => fake()->hexColor(),
            ],
            'brand_config' => [
                'logo_url' => fake()->imageUrl(200, 100),
                'company_name' => fake()->company(),
            ],
            'features' => ['content_generation', 'seo_analysis'],
            'stripe_customer_id' => null,
            'stripe_subscription_id' => null,
        ];
    }

    /**
     * Create a tenant with pro plan.
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => 'pro',
            'tokens_monthly_limit' => 50000,
        ]);
    }

    /**
     * Create a tenant with enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => 'enterprise',
            'tokens_monthly_limit' => 200000,
        ]);
    }

    /**
     * Create an inactive tenant.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Create a suspended tenant.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Create a tenant with tokens used.
     */
    public function withTokensUsed(int $tokensUsed = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tokens_used_current' => $tokensUsed ?? fake()->numberBetween(1000, 8000),
        ]);
    }
}