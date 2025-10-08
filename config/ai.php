<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAI API usage across different use cases.
    | Each use case (campaigns, articles, seo) has optimized settings.
    |
    */

    // Default models per use case
    'models' => [
        'campaigns' => env('AI_MODEL_CAMPAIGNS', 'gpt-4o-mini'),
        'articles' => env('AI_MODEL_ARTICLES', 'gpt-4o'),
        'seo' => env('AI_MODEL_SEO', 'gpt-4o-mini'),
        'internal_links' => env('AI_MODEL_INTERNAL_LINKS', 'gpt-4o-mini'),
        'default' => env('AI_MODEL_DEFAULT', 'gpt-4o-mini'),
    ],

    // Temperature settings (0 = deterministic, 2 = very creative)
    'temperature' => [
        'campaigns' => 0.8,       // More creative for ads
        'articles' => 0.7,        // Balanced for content
        'seo' => 0.5,             // More deterministic for SEO
        'internal_links' => 0.5,  // Deterministic for linking
        'default' => 0.7,
    ],

    // Max tokens per use case
    'max_tokens' => [
        'campaigns' => 1000,      // Short ad copy
        'articles' => 4000,       // Long-form content
        'seo' => 2000,            // Meta descriptions, titles
        'internal_links' => 1500, // Link suggestions
        'default' => 2000,
    ],

    // Retry settings for API failures
    'retry' => [
        'max_attempts' => 3,
        'backoff_multiplier' => 2, // seconds (exponential backoff)
        'initial_delay' => 1,      // seconds
    ],

    // Timeout (seconds)
    'timeout' => env('AI_TIMEOUT', 30),

    // Rate limiting
    'rate_limit' => [
        'requests_per_minute' => env('AI_RATE_LIMIT_RPM', 60),
        'tokens_per_day' => env('AI_RATE_LIMIT_TOKENS_DAY', 100000),
    ],

    // JSON mode settings
    'json_mode' => [
        'enabled' => true,
        'strict' => false, // Set to true for strict JSON schema validation
    ],

    // Token cost per model (USD per 1K tokens) - approximations
    'costs' => [
        'gpt-4o' => [
            'input' => 0.005,
            'output' => 0.015,
        ],
        'gpt-4o-mini' => [
            'input' => 0.00015,
            'output' => 0.0006,
        ],
        'gpt-4-turbo' => [
            'input' => 0.01,
            'output' => 0.03,
        ],
        'gpt-3.5-turbo' => [
            'input' => 0.0005,
            'output' => 0.0015,
        ],
    ],

    // Mock service configuration
    'mock' => [
        'enabled' => env('AI_MOCK_ENABLED', false),
        'fake_keys' => [
            'sk-test',
            'sk-test-key',
            'sk-test-key-replace-with-real-openai-key',
            'your-openai-api-key-here',
            'fake-key',
            'demo-key',
        ],
    ],
];
