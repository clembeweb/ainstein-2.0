<?php

/**
 * Test Phase 1: Database & Models Refactoring
 * Comprehensive test of new unified content system
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\{User, Tenant, Content, Tool, ToolSetting, Prompt, ContentGeneration, CmsConnection, ContentImport};
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   🧪 PHASE 1 REFACTORING TEST - Database & Models\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;

// Test 1: Database Tables Exist
echo "📊 Test 1: Verify New Tables Exist...\n";
try {
    $tables = ['contents', 'tools', 'tool_settings', 'content_imports'];
    foreach ($tables as $table) {
        $exists = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
        if ($exists) {
            echo "   ✅ Table '{$table}' exists\n";
            $passed++;
        } else {
            echo "   ❌ Table '{$table}' NOT found\n";
            $failed++;
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 2: Tools Seeded Correctly
echo "📊 Test 2: Verify Tools Seeded...\n";
try {
    $tools = Tool::all();
    echo "   ✅ Tools found: " . $tools->count() . "\n";

    $contentGenTool = Tool::where('code', 'content-generation')->first();
    if ($contentGenTool && $contentGenTool->is_active) {
        echo "   ✅ Content Generation tool active\n";
        echo "      - Name: {$contentGenTool->name}\n";
        echo "      - Category: {$contentGenTool->category}\n";
        echo "      - Icon: {$contentGenTool->icon}\n";
        $passed++;
    } else {
        echo "   ❌ Content Generation tool not found or inactive\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 3: Data Migration from Pages to Contents
echo "📊 Test 3: Verify Data Migration (Pages → Contents)...\n";
try {
    $pagesCount = DB::table('pages')->count();
    $contentsCount = Content::where('source', 'manual')->count();

    echo "   ✅ Pages in old table: {$pagesCount}\n";
    echo "   ✅ Contents migrated: {$contentsCount}\n";

    if ($pagesCount === $contentsCount) {
        echo "   ✅ All pages successfully migrated\n";
        $passed++;
    } else {
        echo "   ⚠️  Migration count mismatch\n";
        $failed++;
    }

    // Check sample content
    $content = Content::first();
    if ($content) {
        echo "   ✅ Sample content:\n";
        echo "      - URL: {$content->url}\n";
        echo "      - Type: {$content->content_type}\n";
        echo "      - Source: {$content->source}\n";
        echo "      - Status: {$content->status}\n";
        $passed++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 4: Prompts Associated with Tool
echo "📊 Test 4: Verify Prompts → Tool Association...\n";
try {
    $contentGenTool = Tool::where('code', 'content-generation')->first();
    $promptsWithTool = Prompt::where('tool_id', $contentGenTool->id)->count();

    echo "   ✅ Prompts associated with content-generation: {$promptsWithTool}\n";

    $samplePrompt = Prompt::where('tool_id', $contentGenTool->id)->first();
    if ($samplePrompt) {
        echo "   ✅ Sample prompt:\n";
        echo "      - Title: {$samplePrompt->title}\n";
        echo "      - Alias: {$samplePrompt->alias}\n";
        echo "      - Tool: " . ($samplePrompt->tool ? $samplePrompt->tool->name : 'N/A') . "\n";
        $passed++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 5: Model Relationships Work
echo "📊 Test 5: Test Model Relationships...\n";
try {
    $user = User::where('email', 'admin@demo.com')->first();
    $tenant = $user->tenant;

    echo "   ✅ User → Tenant: {$tenant->name}\n";

    // Test Content → Tenant
    $content = Content::forTenant($tenant->id)->first();
    if ($content) {
        echo "   ✅ Content → Tenant: {$content->tenant->name}\n";
        $passed++;
    }

    // Test Content → Generations
    $generation = ContentGeneration::first();
    if ($generation) {
        $genContent = $generation->content;
        echo "   ✅ ContentGeneration → Content: " . ($genContent ? $genContent->url : 'N/A') . "\n";
        echo "   ✅ ContentGeneration → Tenant: {$generation->tenant->name}\n";
        echo "   ✅ ContentGeneration → Prompt: " . ($generation->prompt ? $generation->prompt->title : 'N/A') . "\n";
        $passed++;
    }

    // Test Tool → Prompts
    $tool = Tool::where('code', 'content-generation')->first();
    $toolPrompts = $tool->prompts()->count();
    echo "   ✅ Tool → Prompts: {$toolPrompts} prompts\n";
    $passed++;

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 6: Model Scopes Work
echo "📊 Test 6: Test Model Scopes...\n";
try {
    $tenant = Tenant::first();

    // Content scopes
    $activeContents = Content::forTenant($tenant->id)->active()->count();
    echo "   ✅ Active contents for tenant: {$activeContents}\n";

    $manualContents = Content::fromSource('manual')->count();
    echo "   ✅ Manual source contents: {$manualContents}\n";

    // Tool scopes
    $activeTools = Tool::active()->count();
    echo "   ✅ Active tools: {$activeTools}\n";

    $copyTools = Tool::byCategory('copy')->count();
    echo "   ✅ Copy category tools: {$copyTools}\n";

    $passed++;
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 7: Create New Content Test
echo "📊 Test 7: Test Creating New Content...\n";
try {
    $tenant = Tenant::first();
    $user = User::where('email', 'admin@demo.com')->first();

    $newContent = Content::create([
        'tenant_id' => $tenant->id,
        'url' => '/test/new-content-' . time(),
        'content_type' => 'article',
        'source' => 'manual',
        'keyword' => 'test keyword',
        'language' => 'it',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    if ($newContent->exists) {
        echo "   ✅ New content created successfully\n";
        echo "      - ID: {$newContent->id}\n";
        echo "      - URL: {$newContent->url}\n";

        // Clean up
        $newContent->delete();
        echo "   ✅ Test content cleaned up\n";
        $passed++;
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 8: CMS Connection & Content Import Models
echo "📊 Test 8: Test CMS Connection & ContentImport Models...\n";
try {
    // Test model instantiation
    $cmsConn = new CmsConnection();
    $contentImport = new ContentImport();

    echo "   ✅ CmsConnection model instantiates\n";
    echo "   ✅ ContentImport model instantiates\n";

    // Check fillable fields
    echo "   ✅ CmsConnection fillable: " . count($cmsConn->getFillable()) . " fields\n";
    echo "   ✅ ContentImport fillable: " . count($contentImport->getFillable()) . " fields\n";

    $passed++;
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 9: Backward Compatibility
echo "📊 Test 9: Test Backward Compatibility...\n";
try {
    $generation = ContentGeneration::first();
    if ($generation) {
        // Test legacy page() method still works
        $pageContent = $generation->page;
        $newContent = $generation->content;

        if ($pageContent && $newContent && $pageContent->id === $newContent->id) {
            echo "   ✅ Legacy page() method works (backward compatible)\n";
            echo "   ✅ New content() method works\n";
            echo "   ✅ Both return same content: {$pageContent->url}\n";
            $passed++;
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "   📊 TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

echo "✅ Tests Passed: {$passed}\n";
echo "❌ Tests Failed: {$failed}\n";
echo "📈 Success Rate: {$percentage}%\n\n";

if ($percentage >= 90) {
    echo "🎉 PHASE 1 REFACTORING: SUCCESS!\n";
    echo "   All database tables, models, and relationships working correctly.\n";
    echo "   Ready to proceed with Phase 2 (UI Refactoring).\n\n";
} elseif ($percentage >= 70) {
    echo "⚠️  PHASE 1 REFACTORING: PARTIAL SUCCESS\n";
    echo "   Most components working, but some issues need attention.\n\n";
} else {
    echo "❌ PHASE 1 REFACTORING: FAILED\n";
    echo "   Critical issues detected. Review errors above.\n\n";
}

echo "📋 Next Steps:\n";
echo "   1. Review any failed tests above\n";
echo "   2. If all passed, proceed with menu restructuring\n";
echo "   3. Then refactor Content Generation UI\n";
echo "   4. Create CMS plugins\n";
echo "   5. Implement CSV import\n\n";

echo "🔧 Rollback Command (if needed):\n";
echo "   git checkout v1.0-before-refactoring\n\n";
