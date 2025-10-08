<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Tool;

class ToolsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tools = [
            [
                'id' => (string) Str::ulid(),
                'code' => 'content-generation',
                'name' => 'Content Generation',
                'category' => 'copy',
                'description' => 'AI-powered content generation tool for creating SEO-optimized articles, product descriptions, and marketing copy.',
                'icon' => 'fa-wand-magic-sparkles',
                'is_active' => true,
                'settings_schema' => [
                    'fields' => [
                        [
                            'key' => 'openai_api_key',
                            'label' => 'OpenAI API Key',
                            'type' => 'password',
                            'required' => false,
                            'description' => 'Optional: Use a custom OpenAI API key for this tool'
                        ],
                        [
                            'key' => 'default_model',
                            'label' => 'Default AI Model',
                            'type' => 'select',
                            'options' => ['gpt-4o', 'gpt-4', 'gpt-3.5-turbo'],
                            'default' => 'gpt-4o',
                            'required' => true,
                            'description' => 'Select the default AI model for content generation'
                        ],
                        [
                            'key' => 'max_tokens',
                            'label' => 'Max Tokens per Generation',
                            'type' => 'number',
                            'default' => 2000,
                            'min' => 100,
                            'max' => 4000,
                            'required' => true,
                            'description' => 'Maximum number of tokens to generate per request'
                        ],
                        [
                            'key' => 'temperature',
                            'label' => 'Temperature (Creativity)',
                            'type' => 'number',
                            'default' => 0.7,
                            'min' => 0,
                            'max' => 1,
                            'step' => 0.1,
                            'required' => true,
                            'description' => 'Higher values = more creative, lower = more deterministic'
                        ],
                        [
                            'key' => 'auto_sync_cms',
                            'label' => 'Auto-sync with CMS',
                            'type' => 'boolean',
                            'default' => false,
                            'description' => 'Automatically sync new content with connected CMS'
                        ]
                    ]
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Placeholder for future tools
            [
                'id' => (string) Str::ulid(),
                'code' => 'seo-optimizer',
                'name' => 'SEO Optimizer',
                'category' => 'seo',
                'description' => 'Comprehensive SEO analysis and optimization tool.',
                'icon' => 'fa-chart-line',
                'is_active' => false, // Not yet implemented
                'settings_schema' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'ad-campaign-manager',
                'name' => 'Ad Campaign Manager',
                'category' => 'adv',
                'description' => 'Create and manage advertising campaigns.',
                'icon' => 'fa-bullhorn',
                'is_active' => false, // Not yet implemented
                'settings_schema' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tools as $tool) {
            Tool::updateOrCreate(
                ['code' => $tool['code']],
                $tool
            );
        }

        $this->command->info('✅ Tools seeded successfully!');
        $this->command->info('   - Content Generation (Copy) - ACTIVE');
        $this->command->info('   - SEO Optimizer (SEO) - Placeholder');
        $this->command->info('   - Ad Campaign Manager (ADV) - Placeholder');
    }
}
