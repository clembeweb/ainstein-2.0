<?php

namespace App\Services\AI;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\MockOpenAiService;
use OpenAI;
use OpenAI\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * OpenAI Service - Production-ready AI service with retry logic and token tracking
 *
 * Features:
 * - Chat completion with message history
 * - JSON response parsing
 * - Embeddings generation
 * - Automatic retry with exponential backoff
 * - Token usage tracking for billing
 * - Rate limiting
 * - Mock service fallback for testing
 *
 * @package App\Services\AI
 */
class OpenAIService
{
    protected Client|null $client = null;
    protected string|null $apiKey = null;
    protected string $defaultModel;
    protected MockOpenAiService|null $mockService = null;
    protected bool $useMock = false;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize OpenAI client
     */
    protected function initialize(): void
    {
        // Get API key from PlatformSetting with fallback to .env
        $this->apiKey = PlatformSetting::get('openai_api_key') ?? env('OPENAI_API_KEY');

        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured in platform settings or .env');
        }

        // Get default model
        $this->defaultModel = PlatformSetting::get('openai_default_model', config('ai.models.default'));

        // Check if we should use mock service
        if ($this->shouldUseMock()) {
            Log::info('Using MockOpenAiService for demo/testing purposes');
            $this->useMock = true;
            $this->mockService = new MockOpenAiService();
            return;
        }

        // Initialize real OpenAI client
        $this->client = OpenAI::client($this->apiKey);
    }

    /**
     * Check if mock service should be used
     */
    protected function shouldUseMock(): bool
    {
        if (config('ai.mock.enabled')) {
            return true;
        }

        return in_array($this->apiKey, config('ai.mock.fake_keys'));
    }

    /**
     * Chat completion with message history
     *
     * @param array $messages [['role' => 'user', 'content' => '...']]
     * @param string|null $model Override default model
     * @param array $options ['temperature' => 0.7, 'max_tokens' => 2000, 'use_case' => 'campaigns']
     * @return array ['content' => '...', 'tokens_used' => 123, 'model' => 'gpt-4o', 'finish_reason' => 'stop']
     */
    public function chat(array $messages, string $model = null, array $options = []): array
    {
        // If using mock service
        if ($this->useMock) {
            return $this->mockChat($messages, $model, $options);
        }

        // Determine use case for configuration
        $useCase = $options['use_case'] ?? 'default';

        // Get configuration
        $model = $model ?? config("ai.models.{$useCase}", $this->defaultModel);
        $temperature = $options['temperature'] ?? PlatformSetting::get('openai_temperature') ?? config("ai.temperature.{$useCase}", 0.7);
        $maxTokens = $options['max_tokens'] ?? PlatformSetting::get('openai_max_tokens') ?? config("ai.max_tokens.{$useCase}", 2000);

        // Execute with retry logic
        return $this->retry(function () use ($messages, $model, $temperature, $maxTokens, $options) {
            $params = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ];

            // Add JSON mode if requested
            if (isset($options['json_mode']) && $options['json_mode']) {
                $params['response_format'] = ['type' => 'json_object'];
            }

            $response = $this->client->chat()->create($params);

            $content = $response->choices[0]->message->content ?? '';
            $tokensUsed = $response->usage->totalTokens ?? 0;
            $finishReason = $response->choices[0]->finishReason ?? 'unknown';

            return [
                'content' => trim($content),
                'tokens_used' => $tokensUsed,
                'model' => $model,
                'finish_reason' => $finishReason,
                'success' => true,
            ];
        });
    }

    /**
     * Simple completion (single prompt)
     *
     * @param string $prompt
     * @param string|null $model
     * @param array $options
     * @return array
     */
    public function completion(string $prompt, string $model = null, array $options = []): array
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        // Add system message if provided
        if (isset($options['system_message'])) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $options['system_message']
            ]);
        }

        return $this->chat($messages, $model, $options);
    }

    /**
     * Force JSON response from OpenAI
     *
     * @param array $messages
     * @param string|null $model
     * @param array $options
     * @return array Parsed JSON response
     * @throws Exception if JSON parsing fails
     */
    public function parseJSON(array $messages, string $model = null, array $options = []): array
    {
        // Enable JSON mode
        $options['json_mode'] = true;

        $result = $this->chat($messages, $model, $options);

        if (!$result['success']) {
            throw new Exception('OpenAI request failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        // Parse JSON response
        $json = json_decode($result['content'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON parsing failed', [
                'content' => $result['content'],
                'error' => json_last_error_msg()
            ]);
            throw new Exception('Failed to parse JSON response: ' . json_last_error_msg());
        }

        $result['parsed'] = $json;
        return $result;
    }

    /**
     * Generate embeddings for semantic search
     *
     * @param string|array $text
     * @param string $model
     * @return array
     */
    public function embeddings(string|array $text, string $model = 'text-embedding-3-small'): array
    {
        if ($this->useMock) {
            // Mock embeddings
            return [
                'embeddings' => array_fill(0, is_array($text) ? count($text) : 1, array_fill(0, 1536, 0.0)),
                'tokens_used' => is_array($text) ? count($text) * 10 : 10,
                'model' => $model,
                'success' => true,
            ];
        }

        return $this->retry(function () use ($text, $model) {
            $response = $this->client->embeddings()->create([
                'model' => $model,
                'input' => $text,
            ]);

            $embeddings = [];
            foreach ($response->embeddings as $embedding) {
                $embeddings[] = $embedding->embedding;
            }

            return [
                'embeddings' => $embeddings,
                'tokens_used' => $response->usage->totalTokens ?? 0,
                'model' => $model,
                'success' => true,
            ];
        });
    }

    /**
     * Track token usage for billing
     *
     * @param string $tenantId
     * @param int $tokens
     * @param string $model
     * @param string $source (campaign, article, seo, etc.)
     * @param array $metadata
     * @return void
     */
    public function trackTokenUsage(string $tenantId, int $tokens, string $model, string $source, array $metadata = []): void
    {
        try {
            // Get or create tenant
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                Log::warning('Tenant not found for token tracking', ['tenant_id' => $tenantId]);
                return;
            }

            // Update tenant token usage
            $tenant->increment('tokens_used_current', $tokens);

            // Calculate cost
            $cost = $this->calculateCost($tokens, $model);

            // Log token usage (optional: create separate TokenUsage model if needed)
            Log::info('Token usage tracked', [
                'tenant_id' => $tenantId,
                'tokens' => $tokens,
                'model' => $model,
                'source' => $source,
                'cost' => $cost,
                'metadata' => $metadata,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to track token usage', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate cost based on tokens and model
     *
     * @param int $tokens
     * @param string $model
     * @return float Cost in USD
     */
    protected function calculateCost(int $tokens, string $model): float
    {
        $costs = config('ai.costs');

        // Find matching model cost
        foreach ($costs as $modelKey => $cost) {
            if (str_contains($model, $modelKey)) {
                // Average of input and output cost
                return ($tokens / 1000) * (($cost['input'] + $cost['output']) / 2);
            }
        }

        // Default fallback cost
        return ($tokens / 1000) * 0.001;
    }

    /**
     * Retry logic with exponential backoff
     *
     * @param callable $callback
     * @param int|null $maxAttempts
     * @return mixed
     * @throws Exception
     */
    protected function retry(callable $callback, int $maxAttempts = null): mixed
    {
        $maxAttempts = $maxAttempts ?? config('ai.retry.max_attempts', 3);
        $backoffMultiplier = config('ai.retry.backoff_multiplier', 2);
        $initialDelay = config('ai.retry.initial_delay', 1);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            try {
                return $callback();

            } catch (Exception $e) {
                $attempt++;
                $lastException = $e;

                // Check if we should retry
                if ($attempt >= $maxAttempts || !$this->shouldRetry($e)) {
                    break;
                }

                // Calculate delay (exponential backoff)
                $delay = $initialDelay * pow($backoffMultiplier, $attempt - 1);

                Log::warning('OpenAI request failed, retrying...', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'delay' => $delay,
                    'error' => $e->getMessage(),
                ]);

                sleep($delay);
            }
        }

        // All attempts failed
        Log::error('OpenAI request failed after all retries', [
            'attempts' => $attempt,
            'error' => $lastException->getMessage(),
        ]);

        return [
            'content' => null,
            'tokens_used' => 0,
            'model' => $this->defaultModel,
            'success' => false,
            'error' => $lastException->getMessage(),
        ];
    }

    /**
     * Determine if error should trigger retry
     */
    protected function shouldRetry(Exception $e): bool
    {
        $retryableErrors = [
            'rate_limit_exceeded',
            'timeout',
            'server_error',
            'service_unavailable',
            'bad_gateway',
        ];

        $message = strtolower($e->getMessage());

        foreach ($retryableErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mock chat for testing
     */
    protected function mockChat(array $messages, string $model = null, array $options = []): array
    {
        $lastMessage = end($messages);
        $prompt = $lastMessage['content'] ?? '';

        // If JSON mode is requested, return valid JSON
        if (isset($options['json_mode']) && $options['json_mode']) {
            // Check for campaign-specific prompts
            $promptLower = strtolower($prompt);

            if (str_contains($promptLower, 'rsa') || str_contains($promptLower, 'responsive search ads')) {
                // Return RSA mock data
                return [
                    'content' => json_encode([
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
                    ]),
                    'tokens_used' => 150,
                    'model' => $model ?? 'gpt-4o-mini',
                    'finish_reason' => 'stop',
                    'success' => true,
                ];
            } elseif (str_contains($promptLower, 'pmax') || str_contains($promptLower, 'performance max')) {
                // Return PMAX mock data
                return [
                    'content' => json_encode([
                        'short_titles' => [
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
                    ]),
                    'tokens_used' => 200,
                    'model' => $model ?? 'gpt-4o-mini',
                    'finish_reason' => 'stop',
                    'success' => true,
                ];
            }

            // Generic JSON mock
            return [
                'content' => json_encode([
                    'result' => 'Mock JSON response',
                    'status' => 'success',
                    'data' => ['key' => 'value']
                ]),
                'tokens_used' => 50,
                'model' => $model ?? 'gpt-4o-mini',
                'finish_reason' => 'stop',
                'success' => true,
            ];
        }

        // Normal text response
        $result = $this->mockService->generateContent($prompt, [], $model);

        return [
            'content' => $result['content'],
            'tokens_used' => $result['tokens_used'],
            'model' => $result['model_used'],
            'finish_reason' => 'stop',
            'success' => true,
        ];
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        return array_keys(config('ai.costs'));
    }

    /**
     * Check if service is using mock
     */
    public function isUsingMock(): bool
    {
        return $this->useMock;
    }
}
