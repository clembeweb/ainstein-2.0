<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\ContentGeneration;
use OpenAI;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAiService
{
    private $client;
    private $apiKey;
    private $model;
    private $mockService;
    private $useMock = false;

    public function __construct()
    {
        // Use PlatformSetting::get() with caching
        $this->apiKey = PlatformSetting::get('openai_api_key');

        if (!$this->apiKey) {
            // Fallback to .env if not configured in database
            $this->apiKey = env('OPENAI_API_KEY');
        }

        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured in platform settings or .env');
        }

        $this->model = PlatformSetting::get('openai_default_model', 'gpt-4o-mini');

        // If API key is fake/test, use MockOpenAiService
        if (!$this->isRealApiKey($this->apiKey)) {
            Log::info('Using MockOpenAiService for demo/testing purposes');
            $this->useMock = true;
            $this->mockService = new MockOpenAiService();
            return;
        }

        $this->client = OpenAI::client($this->apiKey);
    }

    /**
     * Check if API key is real or fake/test
     */
    private function isRealApiKey(string $apiKey): bool
    {
        return !in_array($apiKey, [
            'sk-test',
            'sk-test-key',
            'sk-test-key-replace-with-real-openai-key',
            'your-openai-api-key-here',
            'fake-key',
            'demo-key'
        ]);
    }

    /**
     * Generate content using OpenAI
     */
    public function generateContent(string $prompt, array $variables = [], string $model = null): array
    {
        // If using mock service, delegate to it
        if ($this->useMock) {
            $result = $this->mockService->generateContent($prompt, $variables, $model);
            return [
                'content' => $result['content'],
                'tokens_used' => $result['tokens_used'],
                'model' => $result['model_used'],
                'cost' => 0, // Mock service has no cost
                'success' => true
            ];
        }

        try {
            // Replace variables in prompt template
            $processedPrompt = $this->processPromptVariables($prompt, $variables);

            // Get settings from database with fallback to defaults
            $maxTokens = PlatformSetting::get('openai_max_tokens', 2000);
            $temperature = PlatformSetting::get('openai_temperature', 0.7);

            $response = $this->client->chat()->create([
                'model' => $model ?? $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert content writer specialized in SEO-optimized content creation. Provide high-quality, engaging content based on the user\'s requirements.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $processedPrompt
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            $content = $response->choices[0]->message->content;
            $tokensUsed = $response->usage->totalTokens;

            return [
                'content' => trim($content),
                'tokens_used' => $tokensUsed,
                'model' => $model ?? $this->model,
                'cost' => $tokensUsed * 0.000002, // Approximate cost for GPT-3.5/4
                'success' => true
            ];

        } catch (Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());

            return [
                'content' => null,
                'tokens_used' => 0,
                'model' => $model ?? $this->model,
                'cost' => 0,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate meta title for a page
     */
    public function generateMetaTitle(string $keyword, string $category = null): array
    {
        $prompt = "Generate 3 SEO-optimized meta titles for a page targeting the keyword: '{$keyword}'";

        if ($category) {
            $prompt .= " in the {$category} category";
        }

        $prompt .= ". Each title should be:
        - Maximum 60 characters
        - Include the primary keyword naturally
        - Be compelling and click-worthy
        - Optimized for search engines

        Format as a numbered list.";

        return $this->generateContent($prompt);
    }

    /**
     * Generate meta description for a page
     */
    public function generateMetaDescription(string $keyword, string $category = null): array
    {
        $prompt = "Generate 2 SEO-optimized meta descriptions for a page targeting the keyword: '{$keyword}'";

        if ($category) {
            $prompt .= " in the {$category} category";
        }

        $prompt .= ". Each description should be:
        - Maximum 155 characters
        - Include the primary keyword naturally
        - Be compelling with a call-to-action
        - Encourage clicks from search results

        Format as a numbered list.";

        return $this->generateContent($prompt);
    }

    /**
     * Generate blog article content
     */
    public function generateBlogArticle(string $keyword, int $wordCount = 800): array
    {
        $prompt = "Write a comprehensive blog article of approximately {$wordCount} words on the topic: '{$keyword}'.

        Structure the article with:
        - Engaging introduction with the keyword
        - 3-4 main sections with H2 subheadings
        - Natural keyword integration (2-3% density)
        - Practical tips or actionable advice
        - Conclusion with a call-to-action

        Make it informative, engaging, and SEO-optimized.";

        return $this->generateContent($prompt);
    }

    /**
     * Check tenant token limits and update usage
     */
    public function checkAndUpdateTokenUsage(Tenant $tenant, int $tokensToUse): bool
    {
        if ($tenant->tokens_used_current + $tokensToUse > $tenant->tokens_monthly_limit) {
            return false; // Token limit exceeded
        }

        $tenant->increment('tokens_used_current', $tokensToUse);
        return true;
    }

    /**
     * Process prompt template by replacing variables
     */
    private function processPromptVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }

        // Remove any unreplaced variables
        $template = preg_replace('/\{\{[^}]+\}\}/', '[VARIABLE_NOT_PROVIDED]', $template);

        return $template;
    }

    /**
     * Get available OpenAI models
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->client->models()->list();

            $models = collect($response->data)
                ->filter(function ($model) {
                    return str_contains($model->id, 'gpt');
                })
                ->pluck('id')
                ->sort()
                ->values()
                ->toArray();

            return $models;
        } catch (Exception $e) {
            Log::error('Failed to fetch OpenAI models: ' . $e->getMessage());
            return ['gpt-3.5-turbo', 'gpt-4', 'gpt-4o'];
        }
    }

    /**
     * Validate API key
     */
    public function validateApiKey(string $apiKey): bool
    {
        try {
            $client = OpenAI::client($apiKey);
            $client->models()->list();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get token usage statistics for a tenant
     */
    public function getTenantTokenStats(Tenant $tenant): array
    {
        $totalGenerated = ContentGeneration::where('tenant_id', $tenant->id)
            ->sum('tokens_used');

        $thisMonth = ContentGeneration::where('tenant_id', $tenant->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('tokens_used');

        return [
            'total_tokens_used' => $totalGenerated,
            'current_month_tokens' => $thisMonth,
            'monthly_limit' => $tenant->tokens_monthly_limit,
            'remaining_tokens' => $tenant->tokens_monthly_limit - $tenant->tokens_used_current,
            'usage_percentage' => round(($tenant->tokens_used_current / $tenant->tokens_monthly_limit) * 100, 2)
        ];
    }

    /**
     * Generate simple content (for Job compatibility)
     */
    public function generateSimpleContent(string $prompt): string
    {
        try {
            // Get settings from database with fallback to defaults
            $maxTokens = PlatformSetting::get('openai_max_tokens', 2000);
            $temperature = PlatformSetting::get('openai_temperature', 0.7);

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            return $response->choices[0]->message->content ?? 'Failed to generate content';

        } catch (Exception $e) {
            Log::error('OpenAI content generation failed: ' . $e->getMessage());
            throw new Exception('Content generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse JSON response from OpenAI
     * Used by CampaignAssetsGenerator for structured data
     */
    public function parseJSON(string $prompt, array $variables = []): array
    {
        // If using mock service, delegate to it
        if ($this->useMock) {
            $result = $this->mockService->generateContent($prompt, $variables);

            // Parse JSON if the result is a string
            if (is_string($result['content'])) {
                $parsed = json_decode($result['content'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $parsed;
                }
            }

            // Return mock data structure for campaigns
            if (stripos($prompt, 'rsa') !== false) {
                return [
                    'titles' => [
                        "Soluzioni Premium di Qualità",
                        "Offerte Esclusive per Te",
                        "Innovazione al Tuo Servizio",
                        "Qualità Garantita al 100%",
                        "Promozione Speciale Limitata",
                        "Esperienza Unica Garantita",
                        "Servizio Clienti Eccellente",
                        "Risparmia Oggi - Offerta Spec",
                        "Leader nel Settore dal 2024",
                        "Consegna Rapida Garantita",
                        "Prezzi Imbattibili Online",
                        "Soddisfazione Garantita",
                        "Prodotti di Alta Qualità",
                        "Offerta Esclusiva Online",
                        "Scopri le Nostre Novità"
                    ],
                    'descriptions' => [
                        "Scopri la nostra gamma completa di prodotti e servizi di alta qualità. Offerte esclusive e promozioni speciali.",
                        "Qualità superiore e prezzi competitivi. Servizio clienti dedicato e consegna rapida in tutta Italia.",
                        "Leader nel settore con anni di esperienza. Soluzioni innovative per ogni esigenza. Contattaci ora!",
                        "Garanzia di soddisfazione al 100%. Prodotti certificati e servizio post-vendita eccellente."
                    ]
                ];
            } elseif (stripos($prompt, 'pmax') !== false) {
                return [
                    'titles' => [
                        "Qualità Premium Garantita",
                        "Offerte Esclusive Online",
                        "Innovazione e Tecnologia",
                        "Servizio Clienti Top",
                        "Promozioni Imperdibili"
                    ],
                    'long_titles' => [
                        "Scopri la Nostra Gamma Completa di Prodotti e Servizi di Alta Qualità",
                        "Soluzioni Innovative per Ogni Esigenza - Qualità e Convenienza Garantite",
                        "Leader nel Settore con Anni di Esperienza - Affidabilità e Professionalità",
                        "Offerte Esclusive e Promozioni Speciali - Risparmia Oggi con Noi",
                        "Tecnologia Avanzata e Servizio Clienti Eccellente - La Tua Scelta Migliore"
                    ],
                    'descriptions' => [
                        "Scopri la qualità superiore dei nostri prodotti e servizi. Offerte esclusive, consegna rapida e assistenza dedicata.",
                        "Leader nel settore con soluzioni innovative per ogni esigenza. Qualità certificata e prezzi competitivi garantiti.",
                        "Esperienza e professionalità al tuo servizio. Prodotti di alta qualità e servizio clienti eccellente. Contattaci ora!",
                        "Promozioni speciali e offerte limitate. Garanzia di soddisfazione e assistenza post-vendita dedicata. Ordina oggi!",
                        "Innovazione e tecnologia avanzata per risultati superiori. Affidabilità garantita e supporto completo sempre."
                    ]
                ];
            }

            return [];
        }

        try {
            // Process prompt with variables
            $processedPrompt = $this->processPromptVariables($prompt, $variables);

            // Add JSON formatting instruction
            $jsonPrompt = $processedPrompt . "\n\nIMPORTANT: Return only valid JSON without any additional text or markdown formatting.";

            // Get settings from database with fallback to defaults
            $maxTokens = PlatformSetting::get('openai_max_tokens', 2000);
            $temperature = PlatformSetting::get('openai_temperature', 0.3); // Lower temperature for structured output

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an API that returns only valid JSON responses. Do not include any explanatory text or markdown formatting.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $jsonPrompt
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            $content = $response->choices[0]->message->content ?? '{}';

            // Clean the response (remove potential markdown formatting)
            $content = preg_replace('/^```json\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);

            // Parse JSON
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse JSON from OpenAI', [
                    'content' => $content,
                    'error' => json_last_error_msg()
                ]);
                throw new Exception('Failed to parse JSON response: ' . json_last_error_msg());
            }

            return $parsed;

        } catch (Exception $e) {
            Log::error('OpenAI JSON parsing failed: ' . $e->getMessage());
            throw new Exception('JSON parsing failed: ' . $e->getMessage());
        }
    }
}