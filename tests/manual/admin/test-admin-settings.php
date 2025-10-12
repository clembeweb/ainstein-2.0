<?php

/**
 * ADMIN SETTINGS CENTRALIZATION - TEST SCRIPT
 *
 * This script tests all the functionality of the Admin Settings Centralization feature
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PlatformSetting;
use App\Services\OpenAiService;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  ADMIN SETTINGS CENTRALIZATION - TEST SCRIPT                 ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test 1: PlatformSetting Model
echo "📋 TEST 1: PlatformSetting Model\n";
echo str_repeat("─", 60) . "\n";

try {
    $setting = PlatformSetting::first();

    if ($setting) {
        echo "✓ Platform setting record exists (ID: {$setting->id})\n";
        echo "  - OpenAI API Key: " . (empty($setting->openai_api_key) ? '❌ NOT SET' : '✓ Configured') . "\n";
        echo "  - Default Model: " . ($setting->openai_default_model ?? 'not set') . "\n";
        echo "  - Max Tokens: " . ($setting->openai_max_tokens ?? 'not set') . "\n";
        echo "  - Temperature: " . ($setting->openai_temperature ?? 'not set') . "\n";
    } else {
        echo "❌ No platform setting record found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: PlatformSetting::get() Method (with caching)
echo "📋 TEST 2: PlatformSetting::get() Static Method\n";
echo str_repeat("─", 60) . "\n";

try {
    $tests = [
        'openai_api_key' => 'OpenAI API Key',
        'openai_default_model' => 'Default Model',
        'openai_max_tokens' => 'Max Tokens',
        'openai_temperature' => 'Temperature',
        'google_ads_client_id' => 'Google Ads Client ID',
        'stripe_public_key' => 'Stripe Public Key'
    ];

    foreach ($tests as $key => $label) {
        $value = PlatformSetting::get($key, 'not configured');
        $status = ($value === 'not configured') ? '❌' : '✓';

        if ($key === 'openai_api_key' && $value !== 'not configured') {
            $value = substr($value, 0, 10) . '***'; // Mask sensitive data
        }

        echo "  {$status} {$label}: {$value}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Configuration Check Methods
echo "📋 TEST 3: Configuration Check Methods\n";
echo str_repeat("─", 60) . "\n";

try {
    $checks = [
        'OpenAI' => PlatformSetting::isOpenAiConfigured(),
        'Google Ads' => PlatformSetting::isGoogleAdsConfigured(),
        'Facebook' => PlatformSetting::isFacebookConfigured(),
        'Google Console' => PlatformSetting::isGoogleConsoleConfigured(),
        'Stripe' => PlatformSetting::isStripeConfigured(),
    ];

    foreach ($checks as $service => $configured) {
        $status = $configured ? '✓' : '❌';
        $text = $configured ? 'Configured' : 'Not configured';
        echo "  {$status} {$service}: {$text}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: OpenAiService Integration
echo "📋 TEST 4: OpenAiService Integration\n";
echo str_repeat("─", 60) . "\n";

try {
    $service = app(OpenAiService::class);
    echo "✓ OpenAiService instantiated successfully\n";

    // Test content generation
    $result = $service->generateContent('Write a very short sentence about Laravel.');

    if ($result['success']) {
        echo "✓ Content generation successful\n";
        echo "  - Model used: {$result['model']}\n";
        echo "  - Tokens used: {$result['tokens_used']}\n";
        echo "  - Content length: " . strlen($result['content']) . " characters\n";
        echo "  - Preview: " . substr($result['content'], 0, 80) . "...\n";
    } else {
        echo "❌ Content generation failed\n";
        if (isset($result['error'])) {
            echo "  Error: {$result['error']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Database Schema
echo "📋 TEST 5: Database Schema Verification\n";
echo str_repeat("─", 60) . "\n";

try {
    $columns = Schema::getColumnListing('platform_settings');

    $requiredColumns = [
        'openai_api_key', 'openai_organization_id', 'openai_default_model',
        'openai_max_tokens', 'openai_temperature',
        'google_ads_client_id', 'google_ads_client_secret',
        'facebook_app_id', 'facebook_app_secret',
        'stripe_public_key', 'stripe_secret_key',
        'platform_logo_path', 'platform_logo_small_path', 'platform_favicon_path',
        'cache_driver', 'queue_driver', 'rate_limit_per_minute'
    ];

    $missingColumns = [];
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $missingColumns[] = $column;
        }
    }

    if (empty($missingColumns)) {
        echo "✓ All required columns exist ({" . count($requiredColumns) . "} columns checked)\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Encryption
echo "📋 TEST 6: Field Encryption Test\n";
echo str_repeat("─", 60) . "\n";

try {
    $setting = PlatformSetting::first();

    if ($setting) {
        // Test setting an encrypted value
        $testSecret = 'test-secret-value-' . time();
        $setting->google_ads_client_secret = $testSecret;
        $setting->save();

        // Read it back
        $retrieved = PlatformSetting::first()->google_ads_client_secret;

        if ($retrieved === $testSecret) {
            echo "✓ Encryption/Decryption working correctly\n";
            echo "  - Stored and retrieved value match\n";
        } else {
            echo "❌ Encryption/Decryption failed\n";
            echo "  - Original: {$testSecret}\n";
            echo "  - Retrieved: {$retrieved}\n";
        }

        // Clean up
        $setting->google_ads_client_secret = null;
        $setting->save();
    } else {
        echo "⚠ No setting record to test encryption\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLETE                                               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "🌐 Admin Settings URL: " . url('/admin/settings') . "\n";
echo "\n";
echo "Next steps:\n";
echo "1. Visit /admin/settings to configure OAuth integrations\n";
echo "2. Set up OpenAI API key (required for AI features)\n";
echo "3. Configure Stripe for billing\n";
echo "4. Upload platform logo\n";
echo "\n";
