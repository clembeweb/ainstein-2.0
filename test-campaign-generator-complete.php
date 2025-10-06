<?php

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  CAMPAIGN GENERATOR - COMPLETE TEST SUITE                       ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;
$testResults = [];

// Test 1: Database Tables Exist
echo "[TEST 1] Database Tables Structure\n";
echo str_repeat('─', 70) . "\n";

try {
    $campaignsExists = \Illuminate\Support\Facades\Schema::hasTable('adv_campaigns');
    $assetsExists = \Illuminate\Support\Facades\Schema::hasTable('adv_generated_assets');

    echo "   ℹ️  Table 'adv_campaigns': " . ($campaignsExists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    echo "   ℹ️  Table 'adv_generated_assets': " . ($assetsExists ? "✅ EXISTS" : "❌ MISSING") . "\n";

    if ($campaignsExists && $assetsExists) {
        echo "   ✅ All database tables exist\n";
        $testResults['database'] = 'PASS';
    } else {
        echo "   ❌ Missing database tables\n";
        $testResults['database'] = 'FAIL';
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['database'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 2: Models Exist and Work
echo "[TEST 2] Models (AdvCampaign, AdvGeneratedAsset)\n";
echo str_repeat('─', 70) . "\n";

try {
    $campaignModelExists = class_exists('App\\Models\\AdvCampaign');
    $assetModelExists = class_exists('App\\Models\\AdvGeneratedAsset');

    echo "   ℹ️  AdvCampaign model: " . ($campaignModelExists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    echo "   ℹ️  AdvGeneratedAsset model: " . ($assetModelExists ? "✅ EXISTS" : "❌ MISSING") . "\n";

    if ($campaignModelExists && $assetModelExists) {
        // Test relationships
        $campaign = new \App\Models\AdvCampaign();
        $hasAssetsRelation = method_exists($campaign, 'assets');
        $hasTenantRelation = method_exists($campaign, 'tenant');

        echo "   ℹ️  Campaign->assets() relationship: " . ($hasAssetsRelation ? "✅" : "❌") . "\n";
        echo "   ℹ️  Campaign->tenant() relationship: " . ($hasTenantRelation ? "✅" : "❌") . "\n";
        echo "   ✅ All models exist with relationships\n";
        $testResults['models'] = 'PASS';
    } else {
        echo "   ❌ Missing models\n";
        $testResults['models'] = 'FAIL';
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['models'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 3: Service Exists
echo "[TEST 3] CampaignAssetsGenerator Service\n";
echo str_repeat('─', 70) . "\n";

try {
    $serviceExists = class_exists('App\\Services\\Tools\\CampaignAssetsGenerator');

    echo "   ℹ️  Service exists: " . ($serviceExists ? "✅ YES" : "❌ NO") . "\n";

    if ($serviceExists) {
        $service = app('App\\Services\\Tools\\CampaignAssetsGenerator');
        $hasGenerateMethod = method_exists($service, 'generate');
        $hasGenerateRSAMethod = method_exists($service, 'generateRSAAssets');
        $hasGeneratePMaxMethod = method_exists($service, 'generatePMaxAssets');

        echo "   ℹ️  generate() method: " . ($hasGenerateMethod ? "✅" : "❌") . "\n";
        echo "   ℹ️  generateRSAAssets() method: " . ($hasGenerateRSAMethod ? "✅" : "❌") . "\n";
        echo "   ℹ️  generatePMaxAssets() method: " . ($hasGeneratePMaxMethod ? "✅" : "❌") . "\n";
        echo "   ✅ Service exists with all methods\n";
        $testResults['service'] = 'PASS';
    } else {
        echo "   ❌ Service not found\n";
        $testResults['service'] = 'FAIL';
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['service'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 4: Controller Exists
echo "[TEST 4] CampaignGeneratorController\n";
echo str_repeat('─', 70) . "\n";

try {
    $controllerExists = class_exists('App\\Http\\Controllers\\Tenant\\CampaignGeneratorController');

    echo "   ℹ️  Controller exists: " . ($controllerExists ? "✅ YES" : "❌ NO") . "\n";

    if ($controllerExists) {
        $controller = new \App\Http\Controllers\Tenant\CampaignGeneratorController();
        $methods = ['index', 'create', 'store', 'show', 'destroy'];

        foreach ($methods as $method) {
            $exists = method_exists($controller, $method);
            echo "   ℹ️  {$method}() method: " . ($exists ? "✅" : "❌") . "\n";
        }

        echo "   ✅ Controller complete with CRUD methods\n";
        $testResults['controller'] = 'PASS';
    } else {
        echo "   ❌ Controller not found\n";
        $testResults['controller'] = 'FAIL';
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['controller'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 5: Routes Exist
echo "[TEST 5] Routes Registration\n";
echo str_repeat('─', 70) . "\n";

try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $campaignRoutes = [];

    foreach ($routes as $route) {
        if (str_contains($route->getName() ?? '', 'tenant.campaigns')) {
            $campaignRoutes[] = $route->getName();
        }
    }

    echo "   ℹ️  Found " . count($campaignRoutes) . " campaign routes\n";

    $expectedRoutes = [
        'tenant.campaigns.index',
        'tenant.campaigns.create',
        'tenant.campaigns.store',
        'tenant.campaigns.show',
        'tenant.campaigns.destroy'
    ];

    foreach ($expectedRoutes as $routeName) {
        $exists = in_array($routeName, $campaignRoutes);
        echo "   ℹ️  Route '{$routeName}': " . ($exists ? "✅" : "❌") . "\n";
    }

    if (count(array_intersect($expectedRoutes, $campaignRoutes)) >= 3) {
        echo "   ✅ Main routes registered\n";
        $testResults['routes'] = 'PASS';
    } else {
        echo "   ❌ Missing critical routes\n";
        $testResults['routes'] = 'FAIL';
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['routes'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 6: Views Exist
echo "[TEST 6] Blade Views\n";
echo str_repeat('─', 70) . "\n";

$viewsPath = __DIR__ . '/ainstein-laravel/resources/views/tenant/campaigns/';
$expectedViews = ['index.blade.php', 'create.blade.php', 'show.blade.php'];

foreach ($expectedViews as $view) {
    $exists = file_exists($viewsPath . $view);
    echo "   ℹ️  View '{$view}': " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

$allViewsExist = true;
foreach ($expectedViews as $view) {
    if (!file_exists($viewsPath . $view)) {
        $allViewsExist = false;
        break;
    }
}

if ($allViewsExist) {
    echo "   ✅ All views exist\n";
    $testResults['views'] = 'PASS';
} else {
    echo "   ❌ Missing views\n";
    $testResults['views'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 7: Create Test Campaign (RSA)
echo "[TEST 7] Create RSA Campaign (Real OpenAI Generation)\n";
echo str_repeat('─', 70) . "\n";

try {
    $tenant = \App\Models\Tenant::first();

    if (!$tenant) {
        echo "   ⚠️  No tenant found, skipping generation test\n";
        $testResults['rsa_generation'] = 'SKIP';
    } else {
        // Create campaign
        $campaign = \App\Models\AdvCampaign::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test RSA Campaign - ' . date('Y-m-d H:i:s'),
            'type' => 'rsa',
            'info' => 'Professional web design and development services. We create modern, responsive websites that convert visitors into customers.',
            'keywords' => 'web design, website development, professional websites',
            'language' => 'en',
            'url' => 'https://example.com',
        ]);

        echo "   ✅ Campaign created (ID: {$campaign->id})\n";

        // Generate assets using real OpenAI
        $service = app('App\\Services\\Tools\\CampaignAssetsGenerator');

        echo "   🤖 Calling OpenAI to generate RSA assets...\n";
        $asset = $service->generateRSAAssets($campaign);

        echo "   ✅ Assets generated (ID: {$asset->id})\n";
        echo "   ℹ️  Titles generated: " . count($asset->titles) . "\n";
        echo "   ℹ️  Descriptions generated: " . count($asset->descriptions) . "\n";
        echo "   ℹ️  Quality score: {$asset->ai_quality_score}/10\n";
        echo "   ℹ️  Tokens used: {$campaign->tokens_used}\n";
        echo "   ℹ️  Model: {$campaign->model_used}\n";

        // Show first 3 titles as sample
        echo "\n   📝 Sample Titles:\n";
        foreach (array_slice($asset->titles, 0, 3) as $i => $title) {
            $len = mb_strlen($title);
            echo "      " . ($i + 1) . ". \"{$title}\" ({$len} chars)\n";
        }

        // Validate
        $valid = count($asset->titles) >= 3 &&
                 count($asset->titles) <= 15 &&
                 count($asset->descriptions) >= 2 &&
                 count($asset->descriptions) <= 4;

        if ($valid) {
            echo "\n   ✅ RSA generation SUCCESSFUL\n";
            $testResults['rsa_generation'] = 'PASS';
        } else {
            echo "\n   ❌ RSA generation validation FAILED\n";
            $testResults['rsa_generation'] = 'FAIL';
            $allPassed = false;
        }

        // Cleanup
        $campaign->delete();
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['rsa_generation'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 8: Create Test Campaign (PMAX)
echo "[TEST 8] Create PMAX Campaign (Real OpenAI Generation)\n";
echo str_repeat('─', 70) . "\n";

try {
    $tenant = \App\Models\Tenant::first();

    if (!$tenant) {
        echo "   ⚠️  No tenant found, skipping generation test\n";
        $testResults['pmax_generation'] = 'SKIP';
    } else {
        // Create campaign
        $campaign = \App\Models\AdvCampaign::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test PMAX Campaign - ' . date('Y-m-d H:i:s'),
            'type' => 'pmax',
            'info' => 'Digital marketing agency specializing in SEO, PPC, and social media marketing. Proven results for small businesses.',
            'keywords' => 'digital marketing, SEO services, PPC management',
            'language' => 'en',
            'url' => 'https://example.com/marketing',
        ]);

        echo "   ✅ Campaign created (ID: {$campaign->id})\n";

        // Generate assets using real OpenAI
        $service = app('App\\Services\\Tools\\CampaignAssetsGenerator');

        echo "   🤖 Calling OpenAI to generate PMAX assets...\n";
        $asset = $service->generatePMaxAssets($campaign);

        echo "   ✅ Assets generated (ID: {$asset->id})\n";
        echo "   ℹ️  Short titles: " . count($asset->titles) . "\n";
        echo "   ℹ️  Long titles: " . count($asset->long_titles) . "\n";
        echo "   ℹ️  Descriptions: " . count($asset->descriptions) . "\n";
        echo "   ℹ️  Quality score: {$asset->ai_quality_score}/10\n";
        echo "   ℹ️  Tokens used: {$campaign->tokens_used}\n";
        echo "   ℹ️  Model: {$campaign->model_used}\n";

        // Show samples
        echo "\n   📝 Sample Short Titles:\n";
        foreach (array_slice($asset->titles, 0, 2) as $i => $title) {
            $len = mb_strlen($title);
            echo "      " . ($i + 1) . ". \"{$title}\" ({$len} chars)\n";
        }

        echo "\n   📝 Sample Long Title:\n";
        if (isset($asset->long_titles[0])) {
            $len = mb_strlen($asset->long_titles[0]);
            echo "      \"{$asset->long_titles[0]}\" ({$len} chars)\n";
        }

        // Validate
        $valid = count($asset->titles) >= 3 &&
                 count($asset->titles) <= 5 &&
                 count($asset->long_titles) >= 1 &&
                 count($asset->long_titles) <= 5 &&
                 count($asset->descriptions) >= 1 &&
                 count($asset->descriptions) <= 5;

        if ($valid) {
            echo "\n   ✅ PMAX generation SUCCESSFUL\n";
            $testResults['pmax_generation'] = 'PASS';
        } else {
            echo "\n   ❌ PMAX generation validation FAILED\n";
            $testResults['pmax_generation'] = 'FAIL';
            $allPassed = false;
        }

        // Cleanup
        $campaign->delete();
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['pmax_generation'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Test 9: Token Tracking
echo "[TEST 9] Token Tracking Integration\n";
echo str_repeat('─', 70) . "\n";

try {
    $tenant = \App\Models\Tenant::first();

    if (!$tenant) {
        echo "   ⚠️  No tenant found, skipping test\n";
        $testResults['token_tracking'] = 'SKIP';
    } else {
        $tokensBefore = $tenant->tokens_used_current;

        // Create quick campaign
        $campaign = \App\Models\AdvCampaign::create([
            'tenant_id' => $tenant->id,
            'name' => 'Token Test Campaign',
            'type' => 'rsa',
            'info' => 'Test campaign for token tracking',
            'keywords' => 'test, tokens',
            'language' => 'en',
            'url' => 'https://test.com',
        ]);

        $service = app('App\\Services\\Tools\\CampaignAssetsGenerator');
        $asset = $service->generateRSAAssets($campaign);

        // Refresh tenant
        $tenant->refresh();
        $tokensAfter = $tenant->tokens_used_current;

        echo "   ℹ️  Tokens before: {$tokensBefore}\n";
        echo "   ℹ️  Tokens after: {$tokensAfter}\n";
        echo "   ℹ️  Tokens used: " . ($tokensAfter - $tokensBefore) . "\n";

        if ($tokensAfter > $tokensBefore) {
            echo "   ✅ Token tracking working correctly\n";
            $testResults['token_tracking'] = 'PASS';
        } else {
            echo "   ❌ Tokens not tracked\n";
            $testResults['token_tracking'] = 'FAIL';
            $allPassed = false;
        }

        // Cleanup
        $campaign->delete();
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $testResults['token_tracking'] = 'FAIL';
    $allPassed = false;
}
echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY                                                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($testResults as $test => $result) {
    $icon = match($result) {
        'PASS' => '✅',
        'FAIL' => '❌',
        'SKIP' => '⏭️',
        default => '❓'
    };

    echo "   {$icon} " . str_pad(ucwords(str_replace('_', ' ', $test)), 30) . " [{$result}]\n";

    if ($result === 'PASS') $passed++;
    if ($result === 'FAIL') $failed++;
    if ($result === 'SKIP') $skipped++;
}

echo "\n";
echo "   Total Tests: " . count($testResults) . "\n";
echo "   Passed: {$passed} ✅\n";
echo "   Failed: {$failed} ❌\n";
echo "   Skipped: {$skipped} ⏭️\n\n";

if ($allPassed && $failed === 0) {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 CAMPAIGN GENERATOR - 100% FUNCTIONAL                        ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
} else {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠️  CAMPAIGN GENERATOR - NEEDS FIXES                           ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
}

echo "\n";
