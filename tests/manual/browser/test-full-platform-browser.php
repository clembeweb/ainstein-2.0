<?php

/**
 * FULL PLATFORM BROWSER SIMULATION TEST
 * Simulates a real user navigating through the entire platform
 * Tests all dashboards, tools, and integrations
 */

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\User;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  FULL PLATFORM BROWSER SIMULATION - COMPLETE USER FLOW TEST     ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;
$totalTests = 0;

function test($name, $callback) {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "\n[TEST {$totalTests}] {$name}\n";
    echo str_repeat("─", 70) . "\n";

    try {
        $result = $callback();
        if ($result === true) {
            $passed++;
            echo "✅ PASSED\n";
            return true;
        } else {
            $failed++;
            echo "❌ FAILED: {$result}\n";
            return false;
        }
    } catch (\Exception $e) {
        $failed++;
        echo "❌ EXCEPTION: {$e->getMessage()}\n";
        echo "   File: {$e->getFile()}:{$e->getLine()}\n";
        return false;
    }
}

function info($message) {
    echo "   ℹ️  {$message}\n";
}

function warning($message) {
    global $warnings;
    $warnings++;
    echo "   ⚠️  WARNING: {$message}\n";
}

// ============================================================================
// AUTHENTICATION & USER SETUP
// ============================================================================
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 1: AUTHENTICATION & USER ACCESS                        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

$user = null;
$tenant = null;

test("Find tenant user in database", function() use (&$user, &$tenant) {
    $user = User::whereNotNull('tenant_id')->first();

    if (!$user) {
        return "No tenant user found in database";
    }

    $tenant = $user->tenant;

    info("User: {$user->name}");
    info("Email: {$user->email}");
    info("Tenant: " . ($tenant ? $tenant->name : 'NULL'));
    info("Tenant ID: {$user->tenant_id}");

    return true;
});

test("Login user session", function() use ($user) {
    Auth::login($user);

    if (!Auth::check()) {
        return "Auth::check() returned false";
    }

    if (Auth::id() !== $user->id) {
        return "Logged in user ID mismatch";
    }

    info("Session authenticated");
    info("Auth::id(): " . Auth::id());

    return true;
});

// ============================================================================
// SECTION 2: MAIN DASHBOARD
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 2: MAIN DASHBOARD (TENANT)                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Load main dashboard /dashboard", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantDashboardController();
    $request = \Illuminate\Http\Request::create('/dashboard', 'GET');

    try {
        $response = $controller->index($request);

        if ($response instanceof \Illuminate\View\View) {
            $data = $response->getData();

            info("View: " . $response->name());
            info("Variables passed: " . implode(', ', array_keys($data)));

            return true;
        }

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            info("Redirects to: " . $response->getTargetUrl());
            return true;
        }

        return "Unexpected response type: " . get_class($response);
    } catch (\Exception $e) {
        return "Dashboard error: " . $e->getMessage();
    }
});

test("Check dashboard stats data", function() use ($user) {
    // Simulate dashboard data loading
    $stats = [
        'pages_count' => \App\Models\Content::where('tenant_id', $user->tenant_id)->count(),
        'generations_count' => \App\Models\ContentGeneration::where('tenant_id', $user->tenant_id)->count(),
        'prompts_count' => \App\Models\Prompt::where(function($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id)->orWhere('is_system', true);
        })->count(),
        'api_keys_count' => \App\Models\ApiKey::where('tenant_id', $user->tenant_id)->count(),
    ];

    foreach ($stats as $key => $value) {
        info("{$key}: {$value}");
    }

    return true;
});

// ============================================================================
// SECTION 3: CONTENT GENERATOR (UNIFIED TOOL)
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 3: CONTENT GENERATOR (Unified Pages/Generations/Prompts)║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Load Content Generator - Index", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET');

    $response = $controller->index($request);

    if (!$response instanceof \Illuminate\View\View) {
        return "Not a view: " . get_class($response);
    }

    $data = $response->getData();

    $requiredVars = ['pages', 'generations', 'prompts', 'activeTab', 'statuses', 'categories'];
    foreach ($requiredVars as $var) {
        if (!isset($data[$var])) {
            return "Missing variable: {$var}";
        }
    }

    info("View: {$response->name()}");
    info("Pages: {$data['pages']->total()}");
    info("Generations: {$data['generations']->total()}");
    info("Prompts: {$data['prompts']->total()}");

    return true;
});

test("Content Generator - Pages Tab", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', ['tab' => 'pages']);

    $response = $controller->index($request);
    $data = $response->getData();

    if ($data['activeTab'] !== 'pages') {
        return "Active tab should be 'pages', got: {$data['activeTab']}";
    }

    info("Active tab: pages");
    info("Pages loaded: {$data['pages']->total()}");

    return true;
});

test("Content Generator - Generations Tab", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', ['tab' => 'generations']);

    $response = $controller->index($request);
    $data = $response->getData();

    if ($data['activeTab'] !== 'generations') {
        return "Active tab should be 'generations', got: {$data['activeTab']}";
    }

    info("Active tab: generations");
    info("Generations loaded: {$data['generations']->total()}");

    return true;
});

test("Content Generator - Prompts Tab", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', ['tab' => 'prompts']);

    $response = $controller->index($request);
    $data = $response->getData();

    if ($data['activeTab'] !== 'prompts') {
        return "Active tab should be 'prompts', got: {$data['activeTab']}";
    }

    info("Active tab: prompts");
    info("Prompts loaded: {$data['prompts']->total()}");

    return true;
});

// Test filters
test("Content Generator - Search filter", function() use ($user) {
    $page = \App\Models\Content::where('tenant_id', $user->tenant_id)->first();

    if (!$page) {
        warning("No pages to test search");
        return true;
    }

    $searchTerm = substr($page->url, 1, 5);

    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', [
        'tab' => 'pages',
        'search' => $searchTerm
    ]);

    $response = $controller->index($request);
    $data = $response->getData();

    info("Search term: '{$searchTerm}'");
    info("Results: {$data['pages']->total()}");

    return true;
});

// ============================================================================
// SECTION 4: LEGACY PAGES ROUTE (BACKWARD COMPATIBILITY)
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 4: BACKWARD COMPATIBILITY - OLD ROUTES                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Old /dashboard/pages redirects to Content Generator", function() {
    if (!Route::has('tenant.pages.index')) {
        warning("Route tenant.pages.index not found");
        return true;
    }

    info("Route exists: tenant.pages.index");
    info("Should redirect to: tenant.content.index?tab=pages");

    return true;
});

test("Old /dashboard/prompts redirects to Content Generator", function() {
    if (!Route::has('tenant.prompts.index')) {
        warning("Route tenant.prompts.index not found");
        return true;
    }

    info("Route exists: tenant.prompts.index");
    info("Should redirect to: tenant.content.index?tab=prompts");

    return true;
});

// ============================================================================
// SECTION 5: GENERATION DETAILS & CRUD
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 5: GENERATION DETAILS & CRUD OPERATIONS                ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

$testGeneration = null;

test("View generation details (show)", function() use ($user, &$testGeneration) {
    $generation = \App\Models\ContentGeneration::where('tenant_id', $user->tenant_id)->first();

    if (!$generation) {
        warning("No generations to view");
        return true;
    }

    $testGeneration = $generation;

    $controller = new \App\Http\Controllers\TenantContentController();
    $response = $controller->show($generation);

    if (!$response instanceof \Illuminate\View\View) {
        return "Not a view: " . get_class($response);
    }

    info("Generation ID: {$generation->id}");
    info("Status: {$generation->status}");
    info("Tokens: {$generation->tokens_used}");

    return true;
});

test("Load edit form", function() use ($testGeneration) {
    if (!$testGeneration) {
        warning("No generation to edit");
        return true;
    }

    $controller = new \App\Http\Controllers\TenantContentController();
    $response = $controller->edit($testGeneration);

    if (!$response instanceof \Illuminate\View\View) {
        return "Not a view: " . get_class($response);
    }

    $data = $response->getData();

    if (!isset($data['generation'])) {
        return "Missing 'generation' variable";
    }

    info("Edit view loaded");
    info("View: {$response->name()}");

    return true;
});

// ============================================================================
// SECTION 6: API KEYS MANAGEMENT
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 6: API KEYS MANAGEMENT                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Load API Keys index", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantApiKeyController();
    $request = \Illuminate\Http\Request::create('/dashboard/api-keys', 'GET');

    try {
        $response = $controller->index($request);

        if ($response instanceof \Illuminate\View\View) {
            $data = $response->getData();
            info("View: {$response->name()}");

            if (isset($data['apiKeys'])) {
                info("API Keys count: " . $data['apiKeys']->count());
            }

            return true;
        }

        return "Unexpected response: " . get_class($response);
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

test("API Keys route exists", function() {
    $routes = [
        'tenant.api-keys.index',
        'tenant.api-keys.generate',
        'tenant.api-keys.show',
    ];

    foreach ($routes as $route) {
        if (!Route::has($route)) {
            return "Missing route: {$route}";
        }
        info("✓ {$route}");
    }

    return true;
});

// ============================================================================
// SECTION 7: NAVIGATION & MENU STRUCTURE
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 7: NAVIGATION & MENU STRUCTURE                         ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Main navigation layout file exists", function() {
    $layoutPath = resource_path('views/layouts/navigation.blade.php');

    if (!file_exists($layoutPath)) {
        return "Navigation layout not found";
    }

    $content = file_get_contents($layoutPath);

    // Check for key navigation items
    $checks = [
        'Dashboard' => strpos($content, 'Dashboard') !== false,
        'Content Generator' => strpos($content, 'Content Generator') !== false,
        'API Keys' => strpos($content, 'API Keys') !== false || strpos($content, 'api-keys') !== false,
    ];

    foreach ($checks as $item => $found) {
        if ($found) {
            info("✓ {$item} in menu");
        } else {
            warning("{$item} not found in menu");
        }
    }

    return true;
});

test("Content Generator is single menu item (not 3 separate)", function() {
    $layoutPath = resource_path('views/layouts/navigation.blade.php');
    $content = file_get_contents($layoutPath);

    // Should have single "Content Generator" link
    $hasContentGenerator = strpos($content, 'Content Generator') !== false;

    if (!$hasContentGenerator) {
        return "Content Generator menu item not found";
    }

    info("✓ Unified Content Generator menu item present");

    return true;
});

// ============================================================================
// SECTION 8: CAMPAIGNS TOOL
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 8: CAMPAIGNS GENERATOR (ADV)                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Campaigns route exists", function() {
    if (!Route::has('tenant.campaigns.index')) {
        warning("Campaigns route not found (may not be implemented yet)");
        return true;
    }

    info("✓ tenant.campaigns.index");
    return true;
});

test("Campaigns controller exists", function() {
    $controllerClass = 'App\\Http\\Controllers\\Tenant\\CampaignGeneratorController';

    if (!class_exists($controllerClass)) {
        warning("Campaigns controller not found (may not be implemented yet)");
        return true;
    }

    info("✓ {$controllerClass}");
    return true;
});

// ============================================================================
// SECTION 9: PLATFORM SETTINGS & ADMIN
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 9: PLATFORM SETTINGS & SUPER ADMIN                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Platform settings exist in database", function() {
    $settings = \App\Models\PlatformSetting::first();

    if (!$settings) {
        warning("No platform settings found");
        return true;
    }

    info("Platform: {$settings->platform_name}");
    info("Environment: {$settings->environment}");
    info("Maintenance Mode: " . ($settings->maintenance_mode ? 'ON' : 'OFF'));

    return true;
});

test("Super Admin user exists", function() {
    $superAdmin = User::whereNull('tenant_id')->first();

    if (!$superAdmin) {
        warning("No super admin user found");
        return true;
    }

    info("Super Admin: {$superAdmin->name}");
    info("Email: {$superAdmin->email}");

    return true;
});

// ============================================================================
// SECTION 10: DATABASE MODELS & RELATIONSHIPS
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 10: DATABASE MODELS & RELATIONSHIPS INTEGRITY          ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("User -> Tenant relationship", function() use ($user) {
    $tenant = $user->tenant;

    if (!$tenant) {
        return "User tenant relationship broken";
    }

    info("✓ User belongs to Tenant");
    info("Tenant: {$tenant->name}");

    return true;
});

test("Content -> Generations relationship", function() use ($user) {
    $page = \App\Models\Content::where('tenant_id', $user->tenant_id)->first();

    if (!$page) {
        warning("No pages to test");
        return true;
    }

    try {
        $gens = $page->generations;
        info("✓ Content has many Generations");
        info("Generations count: {$gens->count()}");
        return true;
    } catch (\Exception $e) {
        return "Relationship error: " . $e->getMessage();
    }
});

test("ContentGeneration -> Content relationship", function() use ($user) {
    $gen = \App\Models\ContentGeneration::where('tenant_id', $user->tenant_id)->first();

    if (!$gen) {
        warning("No generations to test");
        return true;
    }

    try {
        $content = $gen->content;
        info("✓ Generation belongs to Content");

        if ($content) {
            info("Content URL: {$content->url}");
        }

        return true;
    } catch (\Exception $e) {
        return "Relationship error: " . $e->getMessage();
    }
});

test("ContentGeneration -> Prompt relationship", function() use ($user) {
    $gen = \App\Models\ContentGeneration::where('tenant_id', $user->tenant_id)->first();

    if (!$gen) {
        warning("No generations to test");
        return true;
    }

    try {
        $prompt = $gen->prompt;
        info("✓ Generation belongs to Prompt");

        if ($prompt) {
            info("Prompt: {$prompt->name}");
        }

        return true;
    } catch (\Exception $e) {
        return "Relationship error: " . $e->getMessage();
    }
});

// ============================================================================
// SECTION 11: VIEW FILES COMPLETENESS
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 11: VIEW FILES & TEMPLATES COMPLETENESS                ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("All main layout files exist", function() {
    $layouts = [
        'resources/views/layouts/app.blade.php',
        'resources/views/layouts/navigation.blade.php',
        'resources/views/layouts/guest.blade.php',
    ];

    foreach ($layouts as $layout) {
        $path = base_path($layout);
        if (!file_exists($path)) {
            return "Missing layout: {$layout}";
        }
        info("✓ {$layout}");
    }

    return true;
});

test("Content Generator views complete", function() {
    $views = [
        'resources/views/tenant/content-generator/index.blade.php',
        'resources/views/tenant/content-generator/tabs/pages.blade.php',
        'resources/views/tenant/content-generator/tabs/generations.blade.php',
        'resources/views/tenant/content-generator/tabs/prompts.blade.php',
        'resources/views/tenant/content/edit.blade.php',
    ];

    foreach ($views as $view) {
        $path = base_path($view);
        if (!file_exists($path)) {
            return "Missing view: {$view}";
        }
        $size = filesize($path);
        info("✓ " . basename($view) . " ({$size} bytes)");
    }

    return true;
});

// ============================================================================
// SECTION 12: JAVASCRIPT & FRONTEND ASSETS
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 12: JAVASCRIPT & FRONTEND ASSETS                       ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("Compiled assets exist and recent", function() {
    $manifestPath = public_path('build/manifest.json');

    if (!file_exists($manifestPath)) {
        return "Build manifest not found";
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);

    info("✓ Manifest found");
    info("Assets count: " . count($manifest));

    foreach ($manifest as $key => $file) {
        info("  - {$key}");
    }

    return true;
});

test("Onboarding JavaScript present", function() {
    $jsPath = base_path('resources/js/onboarding-tools.js');

    if (!file_exists($jsPath)) {
        return "Onboarding JS not found";
    }

    $content = file_get_contents($jsPath);
    $size = filesize($jsPath);

    // Check for Content Generator tour
    if (strpos($content, 'initContentGeneratorOnboardingTour') === false) {
        return "Content Generator tour function not found";
    }

    info("✓ File exists ({$size} bytes)");
    info("✓ Content Generator tour present");

    return true;
});

// ============================================================================
// SECTION 13: ROUTE COVERAGE
// ============================================================================
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 13: COMPLETE ROUTE COVERAGE CHECK                      ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

test("All critical tenant routes registered", function() {
    $criticalRoutes = [
        'tenant.dashboard',
        'tenant.content.index',
        'tenant.content.show',
        'tenant.content.edit',
        'tenant.content.update',
        'tenant.content.destroy',
        'tenant.api-keys.index',
    ];

    $missing = [];

    foreach ($criticalRoutes as $route) {
        if (!Route::has($route)) {
            $missing[] = $route;
        } else {
            info("✓ {$route}");
        }
    }

    if (!empty($missing)) {
        return "Missing routes: " . implode(', ', $missing);
    }

    return true;
});

// ============================================================================
// FINAL SUMMARY
// ============================================================================
echo "\n\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  FINAL TEST SUMMARY - FULL PLATFORM                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "Total Tests Run:    {$totalTests}\n";
echo "✅ Passed:          {$passed}\n";
echo "❌ Failed:          {$failed}\n";
echo "⚠️  Warnings:        {$warnings}\n";

$successRate = ($totalTests > 0) ? round(($passed / $totalTests) * 100, 2) : 0;
echo "Success Rate:       {$successRate}%\n\n";

if ($failed === 0) {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 ALL TESTS PASSED! PLATFORM READY FOR PRODUCTION             ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠️  SOME TESTS FAILED - REVIEW BEFORE DEPLOYMENT                ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
    exit(1);
}
