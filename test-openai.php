<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "🧪 Testing OpenAI Integration\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // Test 1: Check Platform Settings
    echo "1️⃣  Checking Platform Settings...\n";
    $settings = App\Models\PlatformSetting::first();

    if (!$settings) {
        echo "   ❌ No platform settings found\n";
        exit(1);
    }

    echo "   ✅ API Key: " . substr($settings->openai_api_key, 0, 20) . "...\n";
    echo "   ✅ Model: " . $settings->openai_model . "\n\n";

    // Test 2: Check if we have a test tenant and page
    echo "2️⃣  Finding test tenant and page...\n";
    $tenant = App\Models\Tenant::where('subdomain', 'demo')->first();

    if (!$tenant) {
        echo "   ❌ Demo tenant not found\n";
        exit(1);
    }

    echo "   ✅ Tenant: {$tenant->name}\n";

    $page = App\Models\Page::where('tenant_id', $tenant->id)->first();

    if (!$page) {
        echo "   ❌ No pages found for demo tenant\n";
        exit(1);
    }

    echo "   ✅ Page: {$page->url_path}\n";
    echo "   ✅ Keyword: {$page->keyword}\n\n";

    // Test 3: Get or create a prompt
    echo "3️⃣  Getting prompt template...\n";
    $prompt = App\Models\Prompt::where('tenant_id', $tenant->id)->first();

    if (!$prompt) {
        echo "   ❌ No prompts found for demo tenant\n";
        exit(1);
    }

    echo "   ✅ Prompt: {$prompt->name}\n";
    echo "   ✅ Template: " . substr($prompt->template, 0, 100) . "...\n\n";

    // Test 4: Test OpenAI Service (Mock or Real)
    echo "4️⃣  Testing OpenAI Service...\n";

    $openAiService = new App\Services\OpenAiService();

    // Prepare test prompt
    $testPrompt = "Write a short meta description (max 160 characters) for a webpage about '{$page->keyword}'.";

    echo "   📝 Test Prompt: {$testPrompt}\n";
    echo "   ⏳ Generating content...\n\n";

    $result = $openAiService->generateContent($testPrompt, [
        'keyword' => $page->keyword,
        'url_path' => $page->url_path
    ]);

    if ($result['success']) {
        echo "   ✅ Generation successful!\n";
        echo "   📄 Content: {$result['content']}\n";
        echo "   🔢 Tokens used: {$result['tokens_used']}\n";
        echo "   💰 Cost: $" . number_format($result['cost'], 4) . "\n\n";

        // Test 5: Save content generation
        echo "5️⃣  Saving content generation...\n";

        // Get a user from the tenant to use as created_by
        $user = App\Models\User::where('tenant_id', $tenant->id)->first();

        $generation = App\Models\ContentGeneration::create([
            'id' => Illuminate\Support\Str::ulid(),
            'tenant_id' => $tenant->id,
            'page_id' => $page->id,
            'prompt_id' => $prompt->id,
            'prompt_type' => 'meta_description',
            'prompt_template' => $testPrompt,
            'generated_content' => $result['content'],
            'meta_description' => $result['content'],
            'tokens_used' => $result['tokens_used'],
            'ai_model' => $settings->openai_model,
            'status' => 'completed',
            'completed_at' => now(),
            'created_by' => $user->id
        ]);

        echo "   ✅ Content saved with ID: {$generation->id}\n\n";

        echo "✨ All tests passed successfully!\n";
        echo str_repeat('=', 50) . "\n";

    } else {
        echo "   ❌ Generation failed: {$result['error']}\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
    exit(1);
}
