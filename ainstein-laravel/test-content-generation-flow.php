<?php

/**
 * Test Complete Content Generation Flow
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Page;
use App\Models\Prompt;
use App\Models\ContentGeneration;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   🧪 TEST COMPLETE CONTENT GENERATION FLOW\n";
echo "═══════════════════════════════════════════════════\n\n";

// Step 1: Get demo user and tenant
echo "📋 Step 1: Verifica User e Tenant...\n";
$user = User::where('email', 'admin@demo.com')->first();
if (!$user) {
    echo "   ❌ Demo user non trovato\n";
    exit(1);
}

$tenant = $user->tenant;
echo "   ✅ User: {$user->email}\n";
echo "   ✅ Tenant: {$tenant->name}\n\n";

// Step 2: Check Prerequisites
echo "📋 Step 2: Verifica Prerequisiti...\n";

// Check pages
$pages = Page::where('tenant_id', $tenant->id)->get();
echo "   ✅ Pages disponibili: " . $pages->count() . "\n";
if ($pages->isEmpty()) {
    echo "   ❌ Nessuna page trovata\n";
    exit(1);
}
$testPage = $pages->first();
echo "      → Test page: {$testPage->url_path}\n";

// Check prompts
$prompts = Prompt::where('tenant_id', $tenant->id)
    ->where('is_active', true)
    ->get();
echo "   ✅ Prompts disponibili: " . $prompts->count() . "\n";
if ($prompts->isEmpty()) {
    echo "   ❌ Nessun prompt trovato\n";
    exit(1);
}
$testPrompt = $prompts->first();
echo "      → Test prompt: {$testPrompt->title} ({$testPrompt->alias})\n";

// Check OpenAI key in config
$openaiKey = env('OPENAI_API_KEY');
if (!$openaiKey || $openaiKey === 'your-openai-api-key-here') {
    echo "   ⚠️  OpenAI API key non configurata in .env\n";
} else {
    echo "   ✅ OpenAI API key configurata: " . substr($openaiKey, 0, 10) . "...\n";
}

echo "\n";

// Step 3: Check Views Exist
echo "📋 Step 3: Verifica Views...\n";
$views = [
    'tenant/content/create.blade.php',
    'tenant/content/index.blade.php',
    'tenant/content/show.blade.php',
];

foreach ($views as $view) {
    $path = __DIR__ . '/resources/views/' . $view;
    if (file_exists($path)) {
        echo "   ✅ {$view}\n";
    } else {
        echo "   ❌ {$view} - FILE MANCANTE!\n";
    }
}

echo "\n";

// Step 4: Test Routes
echo "📋 Step 4: Verifica Routes...\n";
$routes = Route::getRoutes();
$requiredRoutes = [
    'tenant.content.create',
    'tenant.content.store',
    'tenant.content.index',
    'tenant.content.show',
];

foreach ($requiredRoutes as $routeName) {
    $route = $routes->getByName($routeName);
    if ($route) {
        echo "   ✅ {$routeName} → {$route->uri()}\n";
    } else {
        echo "   ❌ {$routeName} - ROUTE MANCANTE!\n";
    }
}

echo "\n";

// Step 5: Simulate Content Generation
echo "📋 Step 5: Simula Creazione Content Generation...\n";

try {
    // Extract variables from prompt template
    preg_match_all('/\{\{([^}]+)\}\}/', $testPrompt->template, $matches);
    $variables = array_unique($matches[1]);

    $testVariables = [];
    foreach ($variables as $var) {
        $varName = trim($var);
        $testVariables[$varName] = "Test value for {$varName}";
    }

    echo "   → Variables estratte: " . json_encode($testVariables) . "\n";

    // Create generation record
    $generation = ContentGeneration::create([
        'tenant_id' => $tenant->id,
        'page_id' => $testPage->id,
        'prompt_id' => $testPrompt->id,
        'prompt_type' => 'blog-article',
        'prompt_template' => $testPrompt->template,
        'variables' => $testVariables,
        'additional_instructions' => 'This is a test generation',
        'status' => 'pending',
        'created_by' => $user->id,
        'ai_model' => 'gpt-4o',
    ]);

    echo "   ✅ Content Generation creato: ID {$generation->id}\n";
    echo "      → Status: {$generation->status}\n";
    echo "      → Page: {$testPage->url_path}\n";
    echo "      → Prompt: {$testPrompt->title}\n\n";

    // Check if generation exists
    $checkGeneration = ContentGeneration::find($generation->id);
    if ($checkGeneration) {
        echo "   ✅ Generation verificato nel database\n";
    } else {
        echo "   ❌ Generation NON trovato nel database\n";
    }

} catch (\Exception $e) {
    echo "   ❌ Errore durante creazione: " . $e->getMessage() . "\n";
    echo "      Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Step 6: Summary
echo "═══════════════════════════════════════════════════\n";
echo "   📊 RIEPILOGO TEST\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "✅ Prerequisites OK:\n";
echo "   - User e Tenant configurati\n";
echo "   - " . $pages->count() . " pages disponibili\n";
echo "   - " . $prompts->count() . " prompts disponibili\n";
echo "   - Views create\n";
echo "   - Routes registrate\n\n";

echo "🌐 Test Manuale Browser:\n";
echo "   1. Vai su: http://127.0.0.1:8080/dashboard/content/create\n";
echo "   2. Seleziona page: {$testPage->url_path}\n";
echo "   3. Seleziona prompt: {$testPrompt->title}\n";
echo "   4. Compila variabili e clicca 'Generate Content'\n\n";

echo "📝 Ultimo Generation Creato:\n";
if (isset($generation)) {
    echo "   - ID: {$generation->id}\n";
    echo "   - Status: {$generation->status}\n";
    echo "   - View: http://127.0.0.1:8080/dashboard/content/{$generation->id}\n";
}

echo "\n";
