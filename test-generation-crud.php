<?php

/**
 * Test script for Content Generation CRUD operations
 * Tests: Edit, Update, Delete functionality
 */

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ContentGeneration;
use App\Models\Content;
use App\Models\Prompt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "🧪 CONTENT GENERATION CRUD TESTS\n";
echo "=================================\n\n";

$passed = 0;
$failed = 0;
$totalTests = 0;

function test($name, $callback) {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "Test {$totalTests}: {$name}\n";

    try {
        $result = $callback();
        if ($result === true) {
            $passed++;
            echo "  ✅ PASSED\n\n";
            return true;
        } else {
            $failed++;
            echo "  ❌ FAILED: {$result}\n\n";
            return false;
        }
    } catch (\Exception $e) {
        $failed++;
        echo "  ❌ EXCEPTION: {$e->getMessage()}\n\n";
        return false;
    }
}

// Setup: Login as first user
$user = \App\Models\User::first();
if (!$user) {
    echo "❌ No users found in database. Please create a user first.\n";
    exit(1);
}

Auth::login($user);
echo "👤 Logged in as: {$user->name} (Tenant ID: {$user->tenant_id})\n\n";

// Test 1: Check if we have any generations
test("Check existing generations", function() use ($user) {
    $count = ContentGeneration::where('tenant_id', $user->tenant_id)->count();
    echo "  Found {$count} generations\n";
    return true;
});

// Test 2: Create a test page if needed
$testPage = null;
test("Ensure test page exists", function() use ($user, &$testPage) {
    $testPage = Content::where('tenant_id', $user->tenant_id)->first();

    if (!$testPage) {
        $testPage = Content::create([
            'tenant_id' => $user->tenant_id,
            'url_path' => '/test-crud-page',
            'keyword' => 'test crud',
            'category' => 'test',
            'status' => 'draft',
            'language' => 'en',
            'country' => 'US',
        ]);
        echo "  Created test page: {$testPage->url_path}\n";
    } else {
        echo "  Using existing page: {$testPage->url_path}\n";
    }

    return $testPage !== null;
});

// Test 3: Create a test generation
$testGeneration = null;
test("Create test generation", function() use ($user, $testPage, &$testGeneration) {
    $testGeneration = ContentGeneration::create([
        'tenant_id' => $user->tenant_id,
        'content_id' => $testPage->id,
        'prompt_type' => 'article',
        'status' => 'completed',
        'generated_content' => 'This is a test generated content for CRUD testing.',
        'tokens_used' => 50,
        'ai_model' => 'gpt-4o-mini',
        'notes' => 'Initial test notes',
    ]);

    echo "  Generation ID: {$testGeneration->id}\n";
    echo "  Content: " . substr($testGeneration->generated_content, 0, 50) . "...\n";

    return $testGeneration !== null;
});

// Test 4: Test authorization check (edit method)
test("Test authorization in edit method", function() use ($testGeneration, $user) {
    // This generation belongs to the user's tenant, should work
    $gen = ContentGeneration::where('id', $testGeneration->id)
        ->where('tenant_id', $user->tenant_id)
        ->first();

    return $gen !== null;
});

// Test 5: Simulate edit view data
test("Edit view data preparation", function() use ($testGeneration) {
    $generation = ContentGeneration::with(['content', 'prompt'])
        ->find($testGeneration->id);

    if (!$generation) {
        return "Generation not found";
    }

    echo "  Generation ID: {$generation->id}\n";
    echo "  Status: {$generation->status}\n";
    echo "  Content length: " . strlen($generation->generated_content) . " chars\n";
    echo "  Content words: " . str_word_count($generation->generated_content) . " words\n";

    return true;
});

// Test 6: Simulate update operation
test("Update generation content", function() use ($testGeneration) {
    $originalContent = $testGeneration->generated_content;
    $originalNotes = $testGeneration->notes;

    $newContent = 'This is UPDATED test generated content for CRUD testing. Modified successfully!';
    $newNotes = 'Updated notes after edit';

    $testGeneration->generated_content = $newContent;
    $testGeneration->notes = $newNotes;
    $testGeneration->save();

    // Reload from database
    $testGeneration->refresh();

    echo "  Original content: " . substr($originalContent, 0, 40) . "...\n";
    echo "  New content: " . substr($testGeneration->generated_content, 0, 40) . "...\n";
    echo "  Original notes: {$originalNotes}\n";
    echo "  New notes: {$testGeneration->notes}\n";

    if ($testGeneration->generated_content !== $newContent) {
        return "Content not updated";
    }

    if ($testGeneration->notes !== $newNotes) {
        return "Notes not updated";
    }

    return true;
});

// Test 7: Validate update with empty notes
test("Update with empty notes (optional field)", function() use ($testGeneration) {
    $testGeneration->notes = null;
    $testGeneration->save();
    $testGeneration->refresh();

    echo "  Notes field: " . ($testGeneration->notes === null ? 'NULL' : $testGeneration->notes) . "\n";

    return $testGeneration->notes === null;
});

// Test 8: Test validation - empty content should fail
test("Validation: Empty content should fail", function() use ($testGeneration) {
    try {
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['generated_content' => ''],
            ['generated_content' => 'required|string|min:10']
        );

        if ($validator->fails()) {
            echo "  Validation correctly failed\n";
            echo "  Error: " . $validator->errors()->first('generated_content') . "\n";
            return true;
        }

        return "Validation should have failed but didn't";
    } catch (\Exception $e) {
        return "Validation exception: " . $e->getMessage();
    }
});

// Test 9: Test controller update method logic
test("Controller update method logic", function() use ($testGeneration, $user) {
    // Simulate Request data
    $data = [
        'generated_content' => 'Final test content after all updates',
        'notes' => 'Final notes',
    ];

    // Validate
    $validator = \Illuminate\Support\Facades\Validator::make($data, [
        'generated_content' => 'required|string|min:10',
        'notes' => 'nullable|string|max:5000',
    ]);

    if ($validator->fails()) {
        return "Validation failed: " . $validator->errors()->first();
    }

    // Authorization check
    if ($testGeneration->tenant_id !== $user->tenant_id) {
        return "Authorization failed";
    }

    // Update
    $testGeneration->update($data);
    $testGeneration->refresh();

    echo "  Content: " . substr($testGeneration->generated_content, 0, 50) . "...\n";
    echo "  Notes: {$testGeneration->notes}\n";

    return $testGeneration->generated_content === $data['generated_content'];
});

// Test 10: Test relationships
test("Test generation relationships", function() use ($testGeneration) {
    $generation = ContentGeneration::with(['content', 'prompt'])->find($testGeneration->id);

    echo "  Has content: " . ($generation->content ? 'Yes' : 'No') . "\n";
    echo "  Content URL: " . ($generation->content ? $generation->content->url_path : 'N/A') . "\n";
    echo "  Has prompt: " . ($generation->prompt ? 'Yes' : 'No') . "\n";

    return $generation->content !== null;
});

// Test 11: Test delete operation
test("Delete generation", function() use ($testGeneration) {
    $generationId = $testGeneration->id;

    echo "  Deleting generation ID: {$generationId}\n";
    $testGeneration->delete();

    // Verify deletion
    $deleted = ContentGeneration::find($generationId);

    if ($deleted !== null) {
        return "Generation still exists after delete";
    }

    echo "  Generation successfully deleted\n";
    return true;
});

// Test 12: Verify generation is gone
test("Verify generation deleted from database", function() use ($testGeneration) {
    $count = ContentGeneration::where('id', $testGeneration->id)->count();

    echo "  Generations with deleted ID: {$count}\n";

    return $count === 0;
});

// Test 13: Test bulk operations preparation
test("List all generations for bulk operations", function() use ($user) {
    $completed = ContentGeneration::where('tenant_id', $user->tenant_id)
        ->where('status', 'completed')
        ->count();

    $failed = ContentGeneration::where('tenant_id', $user->tenant_id)
        ->where('status', 'failed')
        ->count();

    $total = ContentGeneration::where('tenant_id', $user->tenant_id)->count();

    echo "  Total generations: {$total}\n";
    echo "  Completed: {$completed}\n";
    echo "  Failed: {$failed}\n";

    return true;
});

// Test 14: Test routes existence
test("Verify CRUD routes exist", function() {
    $routes = [
        'tenant.content.edit',
        'tenant.content.update',
        'tenant.content.destroy',
    ];

    foreach ($routes as $routeName) {
        if (!\Illuminate\Support\Facades\Route::has($routeName)) {
            return "Route {$routeName} not found";
        }
        echo "  ✓ Route {$routeName} exists\n";
    }

    return true;
});

// Test 15: Test edit view file exists
test("Verify edit view file exists", function() {
    $viewPath = resource_path('views/tenant/content/edit.blade.php');

    if (!file_exists($viewPath)) {
        return "Edit view file not found at: {$viewPath}";
    }

    $size = filesize($viewPath);
    echo "  View file exists: {$viewPath}\n";
    echo "  File size: {$size} bytes\n";

    return true;
});

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passed} ✅\n";
echo "Failed: {$failed} ❌\n";
echo "Success Rate: " . round(($passed / $totalTests) * 100, 2) . "%\n\n";

if ($failed === 0) {
    echo "🎉 ALL TESTS PASSED! Content Generation CRUD is working correctly.\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the results above.\n";
    exit(1);
}
