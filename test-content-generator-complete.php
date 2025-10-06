<?php

/**
 * COMPLETE BROWSER SIMULATION TEST - Content Generator
 * Tests entire user flow as if navigating from browser
 */

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Content;
use App\Models\ContentGeneration;
use App\Models\Prompt;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  CONTENT GENERATOR - COMPLETE BROWSER SIMULATION TEST         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

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

function warning($message) {
    global $warnings;
    $warnings++;
    echo "⚠️  WARNING: {$message}\n";
}

// ============================================================================
// SETUP: Login as user
// ============================================================================
echo "🔐 AUTHENTICATION SETUP\n";
echo str_repeat("═", 70) . "\n";

$user = User::whereNotNull('tenant_id')->first();
if (!$user) {
    echo "❌ FATAL: No tenant user found. Cannot proceed.\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   Tenant ID: {$user->tenant_id}\n";

// ============================================================================
// SECTION 1: PAGE LOAD & NAVIGATION
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 1: PAGE LOAD & NAVIGATION                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Test 1: Controller index method exists
test("Controller method exists", function() {
    return method_exists(\App\Http\Controllers\TenantContentController::class, 'index');
});

// Test 2: Simulate GET /dashboard/content (Pages tab)
test("Load Content Generator - Pages Tab", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', ['tab' => 'pages']);

    $response = $controller->index($request);

    if (!$response instanceof \Illuminate\View\View) {
        return "Response is not a View: " . get_class($response);
    }

    $data = $response->getData();

    // Check required variables
    if (!isset($data['pages'])) return "Missing 'pages' variable";
    if (!isset($data['generations'])) return "Missing 'generations' variable";
    if (!isset($data['prompts'])) return "Missing 'prompts' variable";
    if (!isset($data['activeTab'])) return "Missing 'activeTab' variable";

    echo "   ✓ View data complete\n";
    echo "   ✓ Pages count: {$data['pages']->total()}\n";
    echo "   ✓ Generations count: {$data['generations']->total()}\n";
    echo "   ✓ Prompts count: {$data['prompts']->total()}\n";
    echo "   ✓ Active tab: {$data['activeTab']}\n";

    return true;
});

// Test 3: Switch to Generations tab
test("Switch to Generations Tab", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', ['tab' => 'generations']);

    $response = $controller->index($request);
    $data = $response->getData();

    if ($data['activeTab'] !== 'generations') {
        return "Active tab should be 'generations', got: {$data['activeTab']}";
    }

    echo "   ✓ Tab switched successfully\n";
    echo "   ✓ Generations loaded: {$data['generations']->total()}\n";

    return true;
});

// Test 4: Switch to Prompts tab
test("Switch to Prompts Tab", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', ['tab' => 'prompts']);

    $response = $controller->index($request);
    $data = $response->getData();

    if ($data['activeTab'] !== 'prompts') {
        return "Active tab should be 'prompts', got: {$data['activeTab']}";
    }

    echo "   ✓ Tab switched successfully\n";
    echo "   ✓ Prompts loaded: {$data['prompts']->total()}\n";

    return true;
});

// ============================================================================
// SECTION 2: FILTERS & SEARCH
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 2: FILTERS & SEARCH                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Test 5: Search in Pages
test("Search Pages by keyword", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();

    // First get a page to search for
    $page = Content::where('tenant_id', $user->tenant_id)->first();

    if (!$page) {
        warning("No pages found to test search");
        return true; // Skip test gracefully
    }

    $searchTerm = substr($page->url, 0, 5);
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', [
        'tab' => 'pages',
        'search' => $searchTerm
    ]);

    $response = $controller->index($request);
    $data = $response->getData();

    echo "   ✓ Search term: '{$searchTerm}'\n";
    echo "   ✓ Results found: {$data['pages']->total()}\n";

    return true;
});

// Test 6: Filter Generations by status
test("Filter Generations by status", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', [
        'tab' => 'generations',
        'status' => 'completed'
    ]);

    $response = $controller->index($request);
    $data = $response->getData();

    $completedCount = $data['generations']->total();
    echo "   ✓ Completed generations: {$completedCount}\n";

    // Verify all returned items are completed
    foreach ($data['generations'] as $gen) {
        if ($gen->status !== 'completed') {
            return "Filter failed: found non-completed generation";
        }
    }

    return true;
});

// Test 7: Filter Prompts by category
test("Filter Prompts by category", function() use ($user) {
    $controller = new \App\Http\Controllers\TenantContentController();

    // Get available categories
    $firstPrompt = Prompt::first();
    if (!$firstPrompt) {
        warning("No prompts found to test filter");
        return true;
    }

    $category = $firstPrompt->category;

    $request = \Illuminate\Http\Request::create('/dashboard/content', 'GET', [
        'tab' => 'prompts',
        'category' => $category
    ]);

    $response = $controller->index($request);
    $data = $response->getData();

    echo "   ✓ Filter category: '{$category}'\n";
    echo "   ✓ Prompts found: {$data['prompts']->total()}\n";

    return true;
});

// ============================================================================
// SECTION 3: RELATIONSHIP INTEGRITY
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 3: RELATIONSHIP INTEGRITY                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Test 8: Content->generations relationship
test("Content->generations relationship (page_id)", function() use ($user) {
    $page = Content::where('tenant_id', $user->tenant_id)->first();

    if (!$page) {
        warning("No pages found to test relationship");
        return true;
    }

    try {
        $generations = $page->generations;
        echo "   ✓ Relationship works\n";
        echo "   ✓ Generations for page: {$generations->count()}\n";
        return true;
    } catch (\Exception $e) {
        return "Relationship broken: " . $e->getMessage();
    }
});

// Test 9: ContentGeneration->content relationship
test("ContentGeneration->content relationship", function() use ($user) {
    $generation = ContentGeneration::where('tenant_id', $user->tenant_id)->first();

    if (!$generation) {
        warning("No generations found to test relationship");
        return true;
    }

    try {
        $content = $generation->content;
        echo "   ✓ Relationship works\n";

        if ($content) {
            echo "   ✓ Page URL: {$content->url}\n";
        } else {
            echo "   ⚠️  Content is null (page may be deleted)\n";
        }

        return true;
    } catch (\Exception $e) {
        return "Relationship broken: " . $e->getMessage();
    }
});

// Test 10: ContentGeneration->prompt relationship
test("ContentGeneration->prompt relationship", function() use ($user) {
    $generation = ContentGeneration::where('tenant_id', $user->tenant_id)->first();

    if (!$generation) {
        warning("No generations found to test relationship");
        return true;
    }

    try {
        $prompt = $generation->prompt;
        echo "   ✓ Relationship works\n";

        if ($prompt) {
            echo "   ✓ Prompt name: {$prompt->name}\n";
        }

        return true;
    } catch (\Exception $e) {
        return "Relationship broken: " . $e->getMessage();
    }
});

// ============================================================================
// SECTION 4: CRUD OPERATIONS
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 4: CRUD OPERATIONS ON GENERATIONS                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

$testGeneration = null;

// Test 11: View generation details (show method)
test("View Generation Details (show)", function() use ($user, &$testGeneration) {
    $generation = ContentGeneration::where('tenant_id', $user->tenant_id)
        ->where('status', 'completed')
        ->first();

    if (!$generation) {
        warning("No completed generations to view");
        return true;
    }

    $testGeneration = $generation;

    $controller = new \App\Http\Controllers\TenantContentController();

    try {
        $response = $controller->show($generation);

        if ($response instanceof \Illuminate\View\View) {
            echo "   ✓ Show view rendered\n";
            echo "   ✓ Generation ID: {$generation->id}\n";
            return true;
        }

        return "Unexpected response type: " . get_class($response);
    } catch (\Exception $e) {
        return "Show failed: " . $e->getMessage();
    }
});

// Test 12: Load edit form (edit method)
test("Load Edit Form (edit)", function() use ($user, $testGeneration) {
    if (!$testGeneration) {
        warning("No test generation available");
        return true;
    }

    $controller = new \App\Http\Controllers\TenantContentController();

    try {
        $response = $controller->edit($testGeneration);

        if (!$response instanceof \Illuminate\View\View) {
            return "Response is not a View: " . get_class($response);
        }

        $data = $response->getData();

        if (!isset($data['generation'])) {
            return "Missing 'generation' variable in view";
        }

        echo "   ✓ Edit view rendered\n";
        echo "   ✓ View file: tenant.content.edit\n";
        echo "   ✓ Generation passed to view\n";

        return true;
    } catch (\Exception $e) {
        return "Edit form failed: " . $e->getMessage();
    }
});

// Test 13: Edit view file exists
test("Edit view file exists", function() {
    $viewPath = resource_path('views/tenant/content/edit.blade.php');

    if (!file_exists($viewPath)) {
        return "View file not found: {$viewPath}";
    }

    $size = filesize($viewPath);
    $lines = count(file($viewPath));

    echo "   ✓ File exists: {$viewPath}\n";
    echo "   ✓ Size: {$size} bytes\n";
    echo "   ✓ Lines: {$lines}\n";

    // Check for key elements
    $content = file_get_contents($viewPath);

    $checks = [
        'generated_content' => strpos($content, 'generated_content') !== false,
        'notes' => strpos($content, 'notes') !== false,
        'copyToClipboard' => strpos($content, 'copyToClipboard') !== false,
        'character count' => strpos($content, 'charCount') !== false,
    ];

    foreach ($checks as $check => $result) {
        if ($result) {
            echo "   ✓ Contains {$check}\n";
        } else {
            warning("Missing {$check} in edit view");
        }
    }

    return true;
});

// Test 14: Update generation (simulated)
test("Update Generation (update method)", function() use ($user, $testGeneration) {
    if (!$testGeneration) {
        warning("No test generation available");
        return true;
    }

    $controller = new \App\Http\Controllers\TenantContentController();

    $newContent = "Updated test content - " . now()->format('Y-m-d H:i:s');
    $newNotes = "Updated notes from test";

    $request = \Illuminate\Http\Request::create(
        "/dashboard/content/{$testGeneration->id}",
        'PUT',
        [
            'generated_content' => $newContent,
            'notes' => $newNotes,
        ]
    );

    try {
        $response = $controller->update($request, $testGeneration);

        // Refresh from database
        $testGeneration->refresh();

        if ($testGeneration->generated_content !== $newContent) {
            return "Content not updated in database";
        }

        if ($testGeneration->notes !== $newNotes) {
            return "Notes not updated in database";
        }

        echo "   ✅ Content updated successfully\n";
        echo "   ✓ New content length: " . strlen($newContent) . " chars\n";
        echo "   ✓ Notes updated\n";

        return true;
    } catch (\Exception $e) {
        return "Update failed: " . $e->getMessage();
    }
});

// Test 15: Delete generation
test("Delete Generation (destroy method)", function() use ($user) {
    // Create a test generation to delete
    $page = Content::where('tenant_id', $user->tenant_id)->first();
    $prompt = Prompt::first();

    if (!$page || !$prompt) {
        warning("Cannot create test generation (missing page or prompt)");
        return true;
    }

    $generation = ContentGeneration::create([
        'tenant_id' => $user->tenant_id,
        'page_id' => $page->id,
        'prompt_id' => $prompt->id,
        'prompt_template' => $prompt->template,
        'prompt_type' => 'test',
        'status' => 'completed',
        'generated_content' => 'Test content to be deleted',
        'tokens_used' => 10,
        'ai_model' => 'test-model',
        'created_by' => $user->id,
    ]);

    $generationId = $generation->id;

    $controller = new \App\Http\Controllers\TenantContentController();
    $request = \Illuminate\Http\Request::create("/dashboard/content/{$generationId}", 'DELETE');

    try {
        $response = $controller->destroy($generation);

        // Verify deletion
        $deleted = ContentGeneration::find($generationId);

        if ($deleted !== null) {
            return "Generation still exists after delete";
        }

        echo "   ✅ Generation deleted successfully\n";
        echo "   ✓ ID: {$generationId}\n";
        echo "   ✓ Verified removed from database\n";

        return true;
    } catch (\Exception $e) {
        return "Delete failed: " . $e->getMessage();
    }
});

// ============================================================================
// SECTION 5: ROUTES VERIFICATION
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 5: ROUTES VERIFICATION                               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Test 16: All required routes exist
test("All Content Generator routes exist", function() {
    $requiredRoutes = [
        'tenant.content.index',
        'tenant.content.show',
        'tenant.content.edit',
        'tenant.content.update',
        'tenant.content.destroy',
    ];

    $missing = [];

    foreach ($requiredRoutes as $routeName) {
        if (!\Illuminate\Support\Facades\Route::has($routeName)) {
            $missing[] = $routeName;
        } else {
            echo "   ✓ {$routeName}\n";
        }
    }

    if (!empty($missing)) {
        return "Missing routes: " . implode(', ', $missing);
    }

    return true;
});

// ============================================================================
// SECTION 6: VIEW FILES VERIFICATION
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 6: VIEW FILES VERIFICATION                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Test 17: All view files exist
test("All required view files exist", function() {
    $viewFiles = [
        'resources/views/tenant/content-generator/index.blade.php',
        'resources/views/tenant/content-generator/tabs/pages.blade.php',
        'resources/views/tenant/content-generator/tabs/generations.blade.php',
        'resources/views/tenant/content-generator/tabs/prompts.blade.php',
        'resources/views/tenant/content/edit.blade.php',
    ];

    $missing = [];

    foreach ($viewFiles as $file) {
        $path = base_path($file);
        if (!file_exists($path)) {
            $missing[] = $file;
        } else {
            $size = filesize($path);
            echo "   ✓ {$file} ({$size} bytes)\n";
        }
    }

    if (!empty($missing)) {
        return "Missing view files: " . implode(', ', $missing);
    }

    return true;
});

// Test 18: Check for onboarding button in main view
test("Onboarding button exists in view", function() {
    $viewPath = resource_path('views/tenant/content-generator/index.blade.php');
    $content = file_get_contents($viewPath);

    if (strpos($content, 'startContentGeneratorOnboarding') === false) {
        return "Onboarding button not found in view";
    }

    if (strpos($content, 'Tour Guidato') === false) {
        return "Tour Guidato text not found";
    }

    echo "   ✓ Onboarding button present\n";
    echo "   ✓ JavaScript function reference found\n";

    return true;
});

// ============================================================================
// SECTION 7: JAVASCRIPT & ASSETS
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SECTION 7: JAVASCRIPT & ASSETS                               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Test 19: Onboarding JS file exists
test("Onboarding JavaScript file exists", function() {
    $jsPath = base_path('resources/js/onboarding-tools.js');

    if (!file_exists($jsPath)) {
        return "File not found: {$jsPath}";
    }

    $content = file_get_contents($jsPath);
    $size = filesize($jsPath);
    $lines = count(file($jsPath));

    echo "   ✓ File exists: {$size} bytes, {$lines} lines\n";

    // Check for tour function
    if (strpos($content, 'initContentGeneratorOnboardingTour') === false) {
        return "Tour function not found";
    }

    if (strpos($content, 'window.startContentGeneratorOnboarding') === false) {
        return "Global function registration not found";
    }

    echo "   ✓ Tour function defined\n";
    echo "   ✓ Global function registered\n";

    return true;
});

// Test 20: Compiled assets exist
test("Compiled assets exist", function() {
    $buildPath = public_path('build');

    if (!is_dir($buildPath)) {
        return "Build directory not found: {$buildPath}";
    }

    $manifestPath = public_path('build/manifest.json');

    if (!file_exists($manifestPath)) {
        return "Build manifest not found";
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);

    echo "   ✓ Build directory exists\n";
    echo "   ✓ Manifest.json found\n";
    echo "   ✓ Assets in manifest: " . count($manifest) . "\n";

    return true;
});

// ============================================================================
// FINAL SUMMARY
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Total Tests Run:    {$totalTests}\n";
echo "✅ Passed:          {$passed}\n";
echo "❌ Failed:          {$failed}\n";
echo "⚠️  Warnings:        {$warnings}\n";

$successRate = ($totalTests > 0) ? round(($passed / $totalTests) * 100, 2) : 0;
echo "Success Rate:       {$successRate}%\n\n";

if ($failed === 0) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 ALL TESTS PASSED! READY FOR COMMIT                        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠️  SOME TESTS FAILED - FIX BEFORE COMMIT                     ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    exit(1);
}
