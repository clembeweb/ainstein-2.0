<?php

namespace Database\Factories;

use App\Models\AdvCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdvGeneratedAsset>
 */
class AdvGeneratedAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default: RSA asset with titles and descriptions
        return [
            'campaign_id' => AdvCampaign::factory(),
            'titles' => $this->generateTitles(5),
            'descriptions' => $this->generateDescriptions(3),
            'long_titles' => null,
            'tokens_used' => fake()->numberBetween(300, 1500),
        ];
    }

    /**
     * Create an RSA asset with full set of titles and descriptions.
     */
    public function rsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'titles' => $this->generateTitles(15), // RSA: up to 15 titles
            'descriptions' => $this->generateDescriptions(4), // RSA: up to 4 descriptions
            'long_titles' => null,
        ]);
    }

    /**
     * Create a PMAX asset with short titles, long titles, and descriptions.
     */
    public function pmax(): static
    {
        return $this->state(fn (array $attributes) => [
            'titles' => $this->generateTitles(5), // PMAX: up to 5 short titles
            'long_titles' => $this->generateLongTitles(5), // PMAX: up to 5 long titles
            'descriptions' => $this->generateDescriptions(5), // PMAX: up to 5 descriptions
        ]);
    }

    /**
     * Generate array of short titles (max 30 characters).
     */
    protected function generateTitles(int $count): array
    {
        $titles = [];
        for ($i = 0; $i < $count; $i++) {
            $titles[] = fake()->words(fake()->numberBetween(2, 4), true);
        }
        return $titles;
    }

    /**
     * Generate array of long titles (max 90 characters).
     */
    protected function generateLongTitles(int $count): array
    {
        $longTitles = [];
        for ($i = 0; $i < $count; $i++) {
            $longTitles[] = fake()->sentence(fake()->numberBetween(6, 10));
        }
        return $longTitles;
    }

    /**
     * Generate array of descriptions (max 90 characters).
     */
    protected function generateDescriptions(int $count): array
    {
        $descriptions = [];
        for ($i = 0; $i < $count; $i++) {
            $descriptions[] = fake()->sentence(fake()->numberBetween(8, 12));
        }
        return $descriptions;
    }

    /**
     * Create asset with minimal data.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'titles' => $this->generateTitles(3),
            'descriptions' => $this->generateDescriptions(2),
            'long_titles' => null,
            'tokens_used' => 200,
        ]);
    }
}
