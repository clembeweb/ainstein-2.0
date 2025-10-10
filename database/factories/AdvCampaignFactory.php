<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdvCampaign>
 */
class AdvCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true) . ' Campaign',
            'type' => fake()->randomElement(['rsa', 'pmax']),
            'info' => fake()->paragraph(),
            'keywords' => implode(', ', fake()->words(5)),
            'url' => fake()->url(),
            'language' => 'it',
            'tokens_used' => fake()->numberBetween(100, 2000),
        ];
    }

    /**
     * Create an RSA campaign.
     */
    public function rsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'rsa',
        ]);
    }

    /**
     * Create a PMAX campaign.
     */
    public function pmax(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pmax',
        ]);
    }

    /**
     * Create a campaign in English.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'en',
        ]);
    }

    /**
     * Create a campaign with no tokens used.
     */
    public function noTokens(): static
    {
        return $this->state(fn (array $attributes) => [
            'tokens_used' => null,
        ]);
    }
}
