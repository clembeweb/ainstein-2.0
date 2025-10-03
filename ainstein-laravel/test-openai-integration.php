<?php

/**
 * Test Integrazione OpenAI
 * Verifica che la chiamata API funzioni correttamente
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use App\Models\Page;
use App\Models\Prompt;
use App\Models\ContentGeneration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   🤖 TEST INTEGRAZIONE OPENAI\n";
echo "═══════════════════════════════════════════════════\n\n";

// Verifica configurazione
echo "📋 Step 1: Verifica Configurazione OpenAI...\n";

$apiKey = config('services.openai.api_key');
if (is_callable($apiKey)) {
    $apiKey = $apiKey();
}

$model = config('services.openai.model');
if (is_callable($model)) {
    $model = $model();
}

if (empty($apiKey) || $apiKey === 'your-openai-api-key-here') {
    echo "   ❌ API Key non configurata correttamente\n";
    echo "   Configura OPENAI_API_KEY nel file .env\n\n";
    exit(1);
}

echo "   ✅ API Key configurata\n";
echo "   ✅ Model: {$model}\n\n";

// Verifica tenant e user demo
echo "👤 Step 2: Preparazione Dati Test...\n";

$demoUser = User::where('email', 'admin@demo.com')->first();
if (!$demoUser || !$demoUser->tenant) {
    echo "   ❌ Demo user o tenant non trovati\n";
    exit(1);
}

$tenant = $demoUser->tenant;
echo "   ✅ User: {$demoUser->email}\n";
echo "   ✅ Tenant: {$tenant->name}\n";
echo "   ✅ Token disponibili: {$tenant->tokens_used_current}/{$tenant->tokens_monthly_limit}\n\n";

// Verifica prompt disponibili
$prompts = Prompt::where('tenant_id', $tenant->id)->where('is_active', true)->get();
if ($prompts->isEmpty()) {
    echo "   ❌ Nessun prompt disponibile\n";
    exit(1);
}

$testPrompt = $prompts->first();
echo "   ✅ Prompt test: {$testPrompt->name}\n\n";

// Test 1: Verifica connessione API OpenAI (test semplice)
echo "🔌 Step 3: Test Connessione API OpenAI...\n";

try {
    $testMessage = "Rispondi solo con 'OK' se ricevi questo messaggio.";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => $testMessage
            ]
        ],
        'max_tokens' => 10,
        'temperature' => 0.7,
    ]);

    if ($response->successful()) {
        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? 'No response';
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;

        echo "   ✅ Connessione API riuscita\n";
        echo "   ✅ Risposta: {$content}\n";
        echo "   ✅ Token utilizzati: {$tokensUsed}\n\n";
    } else {
        echo "   ❌ Errore API: " . $response->status() . "\n";
        echo "   " . $response->body() . "\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Generazione contenuto reale
echo "📝 Step 4: Test Generazione Contenuto...\n";

// Usa una pagina esistente o creane una temporanea
$testPage = Page::where('tenant_id', $tenant->id)->first();

if (!$testPage) {
    echo "   ⚠️  Nessuna pagina trovata, creo una pagina di test...\n";
    $testPage = Page::create([
        'id' => Str::ulid(),
        'tenant_id' => $tenant->id,
        'url_path' => '/test/generazione-ai',
        'keyword' => 'intelligenza artificiale',
        'category' => 'tecnologia',
        'status' => 'active',
    ]);
    echo "   ✅ Pagina di test creata\n";
}

echo "   ✅ Pagina: {$testPage->url_path}\n";
echo "   ✅ Keyword: {$testPage->keyword}\n\n";

// Prepara il prompt
$promptTemplate = str_replace('{{keyword}}', $testPage->keyword, $testPrompt->template);
$promptTemplate = str_replace('{{', '', $promptTemplate);
$promptTemplate = str_replace('}}', '', $promptTemplate);

echo "   📋 Prompt preparato:\n";
echo "   " . substr($promptTemplate, 0, 100) . "...\n\n";

// Chiamata API per generazione contenuto
echo "   🚀 Invio richiesta a OpenAI...\n";

try {
    $startTime = microtime(true);

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Sei un esperto copywriter e content creator. Genera contenuti di alta qualità in italiano.'
            ],
            [
                'role' => 'user',
                'content' => $promptTemplate
            ]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7,
    ]);

    $executionTime = round(microtime(true) - $startTime, 2);

    if ($response->successful()) {
        $data = $response->json();
        $generatedContent = $data['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;
        $finishReason = $data['choices'][0]['finish_reason'] ?? 'unknown';

        echo "   ✅ Generazione completata in {$executionTime}s\n";
        echo "   ✅ Token utilizzati: {$tokensUsed}\n";
        echo "   ✅ Finish reason: {$finishReason}\n";
        echo "   ✅ Lunghezza contenuto: " . strlen($generatedContent) . " caratteri\n\n";

        echo "   📄 Contenuto generato (primi 300 caratteri):\n";
        echo "   " . str_repeat('-', 47) . "\n";
        echo "   " . substr($generatedContent, 0, 300) . "...\n";
        echo "   " . str_repeat('-', 47) . "\n\n";

        // Salva la generazione nel database
        $generation = ContentGeneration::create([
            'id' => Str::ulid(),
            'tenant_id' => $tenant->id,
            'user_id' => $demoUser->id,
            'page_id' => $testPage->id,
            'prompt_id' => $testPrompt->id,
            'prompt_type' => 'content',
            'prompt_template' => $promptTemplate,
            'generated_content' => $generatedContent,
            'tokens_used' => $tokensUsed,
            'ai_model' => $model,
            'status' => 'completed',
            'completed_at' => now(),
            'created_by' => $demoUser->id,
        ]);

        echo "   ✅ Generazione salvata nel database (ID: {$generation->id})\n\n";

        // Aggiorna token usage del tenant
        $tenant->tokens_used_current += $tokensUsed;
        $tenant->save();

        echo "   ✅ Token usage aggiornato: {$tenant->tokens_used_current}/{$tenant->tokens_monthly_limit}\n\n";

    } else {
        echo "   ❌ Errore API: " . $response->status() . "\n";
        $errorBody = $response->json();
        echo "   Error: " . ($errorBody['error']['message'] ?? $response->body()) . "\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}

// Test 3: Verifica generazioni salvate
echo "💾 Step 5: Verifica Database...\n";

$totalGenerations = ContentGeneration::where('tenant_id', $tenant->id)->count();
$completedGenerations = ContentGeneration::where('tenant_id', $tenant->id)
    ->where('status', 'completed')
    ->count();

echo "   ✅ Generazioni totali: {$totalGenerations}\n";
echo "   ✅ Generazioni completate: {$completedGenerations}\n";
echo "   ✅ Token totali utilizzati: {$tenant->tokens_used_current}\n\n";

// Summary
echo "═══════════════════════════════════════════════════\n";
echo "   ✨ TEST COMPLETATO CON SUCCESSO\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "📊 Riepilogo:\n";
echo "   ✅ Configurazione OpenAI: OK\n";
echo "   ✅ Connessione API: OK\n";
echo "   ✅ Generazione contenuto: OK\n";
echo "   ✅ Salvataggio database: OK\n";
echo "   ✅ Token tracking: OK\n\n";

echo "🎯 Sistema pronto per generare contenuti!\n\n";

echo "🔗 Testa dal browser:\n";
echo "   1. Login: http://127.0.0.1:8080/login\n";
echo "   2. Email: admin@demo.com\n";
echo "   3. Password: demo123\n";
echo "   4. Vai su Content Generations per vedere i risultati\n\n";
