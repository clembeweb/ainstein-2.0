<?php

/**
 * Test OpenAI Integration
 * Tests actual content generation with OpenAI API
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\{User, Content, Prompt, ContentGeneration};
use App\Jobs\ProcessContentGeneration;
use Illuminate\Support\Facades\{Log, Queue};

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   🤖 OPENAI INTEGRATION TEST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Step 1: Check OpenAI API Key
echo "Step 1: Check OpenAI Configuration...\n";
$apiKey = env('OPENAI_API_KEY');

if (empty($apiKey)) {
    echo "   ❌ OPENAI_API_KEY not configured in .env\n";
    echo "   Please add your OpenAI API key to continue.\n\n";
    exit(1);
}

echo "   ✅ OpenAI API Key configured: " . substr($apiKey, 0, 10) . "...\n";
echo "   ✅ Default Model: " . env('OPENAI_MODEL', 'gpt-4o') . "\n\n";

// Step 2: Get Test Data
echo "Step 2: Prepare Test Data...\n";
try {
    $user = User::where('email', 'admin@demo.com')->first();
    $tenant = $user->tenant;
    $content = Content::where('tenant_id', $tenant->id)->first();
    $prompt = Prompt::where('alias', 'blog-article')->first();

    if (!$content || !$prompt) {
        throw new Exception("Test data not found. Please ensure database is seeded.");
    }

    echo "   ✅ User: {$user->email}\n";
    echo "   ✅ Tenant: {$tenant->name}\n";
    echo "   ✅ Content: {$content->url}\n";
    echo "   ✅ Prompt: {$prompt->title} ({$prompt->alias})\n\n";

} catch (Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
    exit(1);
}

// Step 3: Create Generation Record
echo "Step 3: Create Content Generation...\n";
try {
    $variables = ['keyword' => $content->keyword ?? 'SEO'];

    $generation = ContentGeneration::create([
        'tenant_id' => $tenant->id,
        'page_id' => $content->id,
        'prompt_id' => $prompt->id,
        'prompt_type' => $prompt->alias ?? 'custom',
        'prompt_template' => $prompt->template,
        'variables' => $variables,
        'additional_instructions' => 'This is a test generation to verify OpenAI integration.',
        'status' => 'pending',
        'created_by' => $user->id,
        'ai_model' => env('OPENAI_MODEL', 'gpt-4o'),
    ]);

    echo "   ✅ Generation created: {$generation->id}\n";
    echo "   ✅ Status: {$generation->status}\n";
    echo "   ✅ Variables: " . json_encode($variables) . "\n\n";

} catch (Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
    exit(1);
}

// Step 4: Dispatch Job
echo "Step 4: Dispatch Content Generation Job...\n";
try {
    // Process synchronously for testing
    $job = new ProcessContentGeneration($generation);

    echo "   ℹ️  Processing content generation (this may take 10-30 seconds)...\n";
    $job->handle();

    echo "   ✅ Job processed\n\n";

} catch (Exception $e) {
    echo "   ❌ Job failed: {$e->getMessage()}\n";
    echo "   📝 Stack trace:\n" . $e->getTraceAsString() . "\n\n";

    // Check generation status
    $generation->refresh();
    if ($generation->status === 'failed') {
        echo "   💬 Error message: {$generation->error_message}\n\n";
    }
    exit(1);
}

// Step 5: Verify Results
echo "Step 5: Verify Generation Results...\n";
try {
    $generation->refresh();

    if ($generation->status !== 'completed') {
        echo "   ⚠️  Status: {$generation->status}\n";
        if ($generation->error_message) {
            echo "   ❌ Error: {$generation->error_message}\n";
        }
        throw new Exception("Generation did not complete successfully");
    }

    echo "   ✅ Status: {$generation->status}\n";
    echo "   ✅ Tokens used: {$generation->tokens_used}\n";
    echo "   ✅ Completed at: {$generation->completed_at}\n\n";

    echo "   📝 Generated Content Preview:\n";
    echo "   " . str_repeat("─", 60) . "\n";
    $preview = substr($generation->generated_content, 0, 300);
    $preview = wordwrap($preview, 60, "\n   ");
    echo "   {$preview}";
    if (strlen($generation->generated_content) > 300) {
        echo "...";
    }
    echo "\n   " . str_repeat("─", 60) . "\n\n";

    echo "   ✅ Full content length: " . strlen($generation->generated_content) . " characters\n\n";

} catch (Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
    exit(1);
}

// Final Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "   ✅ OPENAI INTEGRATION TEST: SUCCESS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "📊 Test Summary:\n";
echo "   ✅ OpenAI API Key configured\n";
echo "   ✅ Content generation created\n";
echo "   ✅ Job dispatched and processed\n";
echo "   ✅ Content generated successfully\n";
echo "   ✅ Database persistence verified\n\n";

echo "🔍 Generation Details:\n";
echo "   ID: {$generation->id}\n";
echo "   Content URL: {$content->url}\n";
echo "   Prompt: {$prompt->alias}\n";
echo "   Status: {$generation->status}\n";
echo "   Tokens: {$generation->tokens_used}\n";
echo "   Characters: " . strlen($generation->generated_content) . "\n\n";

echo "🌐 View in Browser:\n";
echo "   http://127.0.0.1:8080/dashboard/content/{$generation->id}\n\n";

echo "✨ All systems operational!\n\n";
