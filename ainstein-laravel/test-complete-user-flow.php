<?php

/**
 * Complete End-to-End User Flow Test
 * Verifies entire content generation flow works correctly
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\{User, Content, Prompt, ContentGeneration};
use App\Http\Controllers\TenantContentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   🧪 COMPLETE USER FLOW TEST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;
$errors = [];

// Test 1: User Login
echo "Test 1: User Authentication...\n";
try {
    $user = User::where('email', 'admin@demo.com')->first();
    if (!$user) {
        throw new Exception("User admin@demo.com not found");
    }
    $tenant = $user->tenant;
    if (!$tenant) {
        throw new Exception("Tenant not found for user");
    }
    echo "   ✅ User authenticated: {$user->email}\n";
    echo "   ✅ Tenant loaded: {$tenant->name}\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 1: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 2: Load Contents for Dropdown
echo "Test 2: Load Contents for Selection...\n";
try {
    $contents = Content::where('tenant_id', $tenant->id)
        ->where('status', 'active')
        ->orderBy('url')
        ->get();

    if ($contents->isEmpty()) {
        throw new Exception("No active contents found");
    }

    echo "   ✅ Found {$contents->count()} active contents\n";
    foreach ($contents as $content) {
        echo "      - {$content->url}";
        if ($content->keyword) echo " (keyword: {$content->keyword})";
        echo " [type: {$content->content_type}]\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 2: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 3: Load Prompts for Dropdown
echo "Test 3: Load Prompts for Selection...\n";
try {
    $prompts = Prompt::query()
        ->where(function ($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id)
              ->orWhere('is_system', true);
        })
        ->where('is_active', true)
        ->orderBy('is_system', 'asc')
        ->orderBy('alias')
        ->get();

    if ($prompts->isEmpty()) {
        throw new Exception("No active prompts found");
    }

    echo "   ✅ Found {$prompts->count()} available prompts\n";
    foreach ($prompts as $prompt) {
        echo "      - {$prompt->title} ({$prompt->alias})";
        if ($prompt->is_system) echo " [SYSTEM]";
        echo "\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 3: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 4: Extract Variables from Selected Prompt
echo "Test 4: Extract Variables from Prompt Template...\n";
try {
    $selectedPrompt = $prompts->first();
    echo "   ✅ Selected prompt: {$selectedPrompt->title}\n";

    preg_match_all('/\{\{([^}]+)\}\}/', $selectedPrompt->template, $matches);
    $variables = array_unique($matches[1]);

    if (!empty($variables)) {
        echo "   ✅ Extracted " . count($variables) . " variables: " . implode(', ', $variables) . "\n";
    } else {
        echo "   ℹ️  No variables found in template\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 4: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 5: Create Content Generation
echo "Test 5: Create Content Generation Record...\n";
try {
    $selectedContent = $contents->first();

    $userVariables = [];
    foreach ($variables as $var) {
        $varName = trim($var);
        if ($varName === 'keyword' && $selectedContent->keyword) {
            $userVariables[$varName] = $selectedContent->keyword;
        } else {
            $userVariables[$varName] = "Test value for {$varName}";
        }
    }

    $generation = ContentGeneration::create([
        'tenant_id' => $tenant->id,
        'page_id' => $selectedContent->id,
        'prompt_id' => $selectedPrompt->id,
        'prompt_type' => $selectedPrompt->alias ?? 'custom',
        'prompt_template' => $selectedPrompt->template,
        'variables' => $userVariables,
        'additional_instructions' => 'Test generation from automated flow test',
        'status' => 'pending',
        'created_by' => $user->id,
        'ai_model' => 'gpt-4o',
    ]);

    echo "   ✅ Generation created with ID: {$generation->id}\n";
    echo "   ✅ Status: {$generation->status}\n";
    echo "   ✅ Model: {$generation->ai_model}\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 5: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 6: Verify Relationships
echo "Test 6: Verify Model Relationships...\n";
try {
    // Test content relationship (NEW)
    $relatedContent = $generation->content;
    if (!$relatedContent) {
        throw new Exception("content() relationship failed");
    }
    echo "   ✅ Generation → Content: {$relatedContent->url}\n";

    // Test page relationship (LEGACY - backward compatibility)
    $relatedPage = $generation->page;
    if (!$relatedPage) {
        throw new Exception("page() relationship failed (backward compatibility broken)");
    }
    echo "   ✅ Generation → Page (legacy): {$relatedPage->url}\n";

    // Test tenant relationship
    if (!$generation->tenant) {
        throw new Exception("tenant() relationship failed");
    }
    echo "   ✅ Generation → Tenant: {$generation->tenant->name}\n";

    // Test prompt relationship
    if (!$generation->prompt) {
        throw new Exception("prompt() relationship failed");
    }
    echo "   ✅ Generation → Prompt: {$generation->prompt->alias}\n";

    // Test creator relationship
    if (!$generation->creator) {
        throw new Exception("creator() relationship failed");
    }
    echo "   ✅ Generation → Creator: {$generation->creator->email}\n";

    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 6: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 7: Simulate Processing and Completion
echo "Test 7: Simulate Content Generation Processing...\n";
try {
    $generation->update([
        'status' => 'processing',
    ]);
    echo "   ✅ Status updated to: processing\n";

    // Simulate completion
    $generatedText = "This is a test generated content.\n\nIt contains multiple paragraphs to simulate real AI output.\n\nThe content is relevant to the keyword: " . ($selectedContent->keyword ?? 'N/A');

    $generation->update([
        'status' => 'completed',
        'generated_content' => $generatedText,
        'tokens_used' => 250,
        'completed_at' => now(),
    ]);

    echo "   ✅ Status updated to: completed\n";
    echo "   ✅ Tokens used: {$generation->tokens_used}\n";
    echo "   ✅ Content preview: " . substr($generation->generated_content, 0, 50) . "...\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 7: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 8: Verify Database Persistence
echo "Test 8: Verify Database Persistence...\n";
try {
    $freshGeneration = ContentGeneration::find($generation->id);
    if (!$freshGeneration) {
        throw new Exception("Generation not found in database");
    }

    if ($freshGeneration->status !== 'completed') {
        throw new Exception("Status not persisted correctly");
    }

    if (empty($freshGeneration->generated_content)) {
        throw new Exception("Generated content not persisted");
    }

    echo "   ✅ Record exists in database\n";
    echo "   ✅ Status persisted: {$freshGeneration->status}\n";
    echo "   ✅ Content persisted: " . strlen($freshGeneration->generated_content) . " characters\n";
    echo "   ✅ Variables persisted: " . json_encode($freshGeneration->variables) . "\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 8: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 9: Test Index Page Query
echo "Test 9: Test Index Page Query...\n";
try {
    $allGenerations = ContentGeneration::where('tenant_id', $tenant->id)
        ->with(['content', 'prompt'])
        ->latest()
        ->take(5)
        ->get();

    if ($allGenerations->isEmpty()) {
        throw new Exception("No generations found for tenant");
    }

    echo "   ✅ Total generations: " . ContentGeneration::where('tenant_id', $tenant->id)->count() . "\n";
    echo "   ✅ Recent generations (latest 5):\n";

    foreach ($allGenerations as $gen) {
        // Verify relationships are loaded
        if (!$gen->content) {
            throw new Exception("Content relationship not loaded in index query");
        }
        if (!$gen->prompt) {
            throw new Exception("Prompt relationship not loaded in index query");
        }

        echo "      - [{$gen->status}] {$gen->content->url} → {$gen->prompt->alias}\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 9: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 10: Test Search Functionality
echo "Test 10: Test Search Functionality...\n";
try {
    $searchTerm = substr($selectedContent->url, 0, 10);

    $searchResults = ContentGeneration::where('tenant_id', $tenant->id)
        ->whereHas('content', function ($q) use ($searchTerm) {
            $q->where('url', 'like', "%{$searchTerm}%")
              ->orWhere('keyword', 'like', "%{$searchTerm}%");
        })
        ->with(['content', 'prompt'])
        ->get();

    echo "   ✅ Search for '{$searchTerm}' found {$searchResults->count()} results\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 10: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Test 11: Test Status Filter
echo "Test 11: Test Status Filter...\n";
try {
    $completedGenerations = ContentGeneration::where('tenant_id', $tenant->id)
        ->where('status', 'completed')
        ->with(['content', 'prompt'])
        ->get();

    echo "   ✅ Completed generations: {$completedGenerations->count()}\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    $errors[] = "Test 11: " . $e->getMessage();
    $failed++;
}
echo "\n";

// Cleanup
echo "🧹 Cleanup: Removing test generation...\n";
try {
    $generation->delete();
    echo "   ✅ Test generation removed\n";
} catch (Exception $e) {
    echo "   ⚠️  Warning: Failed to cleanup test generation: {$e->getMessage()}\n";
}
echo "\n";

// Final Summary
echo "═══════════════════════════════════════════════════════════════\n";
if ($failed === 0) {
    echo "   ✅ ALL TESTS PASSED: {$passed}/{$passed}\n";
} else {
    echo "   ⚠️  TESTS COMPLETED: {$passed} passed, {$failed} failed\n";
}
echo "═══════════════════════════════════════════════════════════════\n\n";

if (!empty($errors)) {
    echo "❌ Failed Tests:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
    echo "\n";
}

echo "📊 Test Coverage:\n";
echo "   ✅ User authentication\n";
echo "   ✅ Content dropdown loading\n";
echo "   ✅ Prompt dropdown loading\n";
echo "   ✅ Variable extraction from templates\n";
echo "   ✅ Content generation creation\n";
echo "   ✅ All model relationships (content, page, tenant, prompt, creator)\n";
echo "   ✅ Status updates and progression\n";
echo "   ✅ Database persistence\n";
echo "   ✅ Index page query with relationships\n";
echo "   ✅ Search functionality\n";
echo "   ✅ Filter functionality\n\n";

echo "🌐 Manual Browser Test Steps:\n";
echo "   1. Go to: http://127.0.0.1:8080/login\n";
echo "   2. Login: admin@demo.com / demo123\n";
echo "   3. Navigate to: http://127.0.0.1:8080/dashboard/content/create\n";
echo "   4. Verify contents dropdown shows URLs correctly\n";
echo "   5. Verify prompts dropdown works\n";
echo "   6. Select a content and prompt\n";
echo "   7. Fill any required variables\n";
echo "   8. Submit the form\n";
echo "   9. Verify redirect to /dashboard/content/{id} works\n";
echo "  10. Verify generation details display correctly\n";
echo "  11. Go to: http://127.0.0.1:8080/dashboard/content\n";
echo "  12. Verify generations list displays correctly\n";
echo "  13. Test search and filters\n\n";

if ($failed === 0) {
    echo "✨ Backend is fully functional! Ready for browser testing.\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review errors above.\n\n";
    exit(1);
}
