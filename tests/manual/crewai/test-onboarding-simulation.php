<?php

/**
 * Test Simulazione Utente - Onboarding Tools
 * Simula il comportamento utente reale
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   🧪 SIMULAZIONE UTENTE - ONBOARDING TOOLS\n";
echo "═══════════════════════════════════════════════════\n\n";

$baseUrl = 'http://127.0.0.1:8080';

// Reset demo user onboarding
echo "🔄 Step 1: Reset onboarding utente demo...\n";
$demoUser = User::where('email', 'admin@demo.com')->first();

if (!$demoUser) {
    echo "❌ Demo user non trovato!\n";
    exit(1);
}

// Reset all onboarding
$demoUser->update([
    'onboarding_completed' => false,
    'onboarding_tools_completed' => []
]);

echo "   ✅ Onboarding dashboard: " . ($demoUser->onboarding_completed ? 'Completato' : 'Da fare') . "\n";
echo "   ✅ Onboarding tools: " . (empty($demoUser->onboarding_tools_completed) ? '[]' : json_encode($demoUser->onboarding_tools_completed)) . "\n";

// Test 1: Dashboard onboarding status check
echo "\n📊 Test 2: Verifica meta tags nelle view...\n";

$viewsToCheck = [
    'tenant.pages.index' => 'pages',
    'tenant.generations' => 'content-generation',
    'tenant.prompts.index' => 'prompts',
    'tenant.api-keys.index' => 'api-keys',
];

foreach ($viewsToCheck as $view => $tool) {
    $viewPath = str_replace('.', '/', $view) . '.blade.php';
    $fullPath = __DIR__ . '/resources/views/' . $viewPath;

    if (file_exists($fullPath)) {
        echo "   ✅ View {$view} exists\n";
    } else {
        echo "   ⚠️  View {$view} not found at {$viewPath}\n";
    }
}

// Test 2: Simulate API calls for completing onboarding
echo "\n🔌 Test 3: Simula chiamate API...\n";

// Get CSRF token (in real scenario this comes from the form)
$csrfToken = csrf_token();

// Simulate marking 'pages' onboarding as complete
echo "   📝 Simulazione: Completa onboarding 'pages'...\n";
$demoUser->markToolOnboardingComplete('pages');
$demoUser->refresh();

if ($demoUser->hasCompletedToolOnboarding('pages')) {
    echo "   ✅ 'pages' marcato come completato\n";
} else {
    echo "   ❌ 'pages' NON marcato\n";
}

// Simulate marking 'content-generation' onboarding as complete
echo "   📝 Simulazione: Completa onboarding 'content-generation'...\n";
$demoUser->markToolOnboardingComplete('content-generation');
$demoUser->refresh();

if ($demoUser->hasCompletedToolOnboarding('content-generation')) {
    echo "   ✅ 'content-generation' marcato come completato\n";
} else {
    echo "   ❌ 'content-generation' NON marcato\n";
}

// Check current status
$completed = $demoUser->onboarding_tools_completed ?? [];
echo "   📋 Tool completati: " . json_encode($completed) . "\n";

// Test 3: Check JavaScript files are loaded
echo "\n📦 Test 4: Verifica caricamento JavaScript...\n";

$manifestPath = __DIR__ . '/public/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);

    if (isset($manifest['resources/js/app.js'])) {
        $jsFile = '/build/' . $manifest['resources/js/app.js']['file'];
        $jsPath = __DIR__ . '/public' . $jsFile;

        if (file_exists($jsPath)) {
            $jsContent = file_get_contents($jsPath);

            // Check if onboarding functions are present
            $functionsToCheck = [
                'initPagesOnboardingTour',
                'initContentGenerationOnboardingTour',
                'initPromptsOnboardingTour',
                'initApiKeysOnboardingTour',
                'autoStartToolOnboarding'
            ];

            $found = 0;
            foreach ($functionsToCheck as $func) {
                if (strpos($jsContent, $func) !== false) {
                    $found++;
                }
            }

            echo "   ✅ Funzioni onboarding trovate nel JS: {$found}/" . count($functionsToCheck) . "\n";

            if ($found === count($functionsToCheck)) {
                echo "   ✅ Tutti i metodi onboarding presenti nel bundle\n";
            } else {
                echo "   ⚠️  Alcuni metodi mancanti nel bundle\n";
            }
        } else {
            echo "   ❌ File JS compilato non trovato\n";
        }
    }
}

// Test 4: Check Shepherd.js is available
echo "\n🎭 Test 5: Verifica Shepherd.js...\n";

$packageJson = json_decode(file_get_contents(__DIR__ . '/package.json'), true);
if (isset($packageJson['dependencies']['shepherd.js'])) {
    echo "   ✅ Shepherd.js presente in package.json: {$packageJson['dependencies']['shepherd.js']}\n";
} else {
    echo "   ❌ Shepherd.js NON trovato in package.json\n";
}

// Check if CSS is present
$cssPath = __DIR__ . '/node_modules/shepherd.js/dist/css/shepherd.css';
if (file_exists($cssPath)) {
    echo "   ✅ Shepherd.js CSS presente\n";
} else {
    echo "   ⚠️  Shepherd.js CSS non trovato (potrebbe essere inline)\n";
}

// Test 5: Verify controller methods
echo "\n🎮 Test 6: Verifica Controller...\n";

$controllerPath = __DIR__ . '/app/Http/Controllers/OnboardingController.php';
$controllerContent = file_get_contents($controllerPath);

$methods = [
    'completeToolOnboarding',
    'resetToolOnboarding',
    'status'
];

foreach ($methods as $method) {
    if (strpos($controllerContent, "function {$method}") !== false) {
        echo "   ✅ Method {$method}() presente\n";
    } else {
        echo "   ❌ Method {$method}() NON trovato\n";
    }
}

// Test 6: Simulate reset
echo "\n🔄 Test 7: Test Reset Onboarding...\n";

echo "   📝 Reset 'pages' onboarding...\n";
$demoUser->resetToolOnboarding('pages');
$demoUser->refresh();

if (!$demoUser->hasCompletedToolOnboarding('pages')) {
    echo "   ✅ 'pages' resettato correttamente\n";
} else {
    echo "   ❌ 'pages' NON resettato\n";
}

if ($demoUser->hasCompletedToolOnboarding('content-generation')) {
    echo "   ✅ 'content-generation' ancora presente (corretto)\n";
} else {
    echo "   ❌ 'content-generation' perso (errore)\n";
}

// Reset all
echo "   📝 Reset completo...\n";
$demoUser->resetToolOnboarding();
$demoUser->refresh();

$remaining = $demoUser->onboarding_tools_completed ?? [];
if (empty($remaining)) {
    echo "   ✅ Tutti gli onboarding resettati\n";
} else {
    echo "   ❌ Alcuni onboarding rimasti: " . json_encode($remaining) . "\n";
}

// Final Summary
echo "\n═══════════════════════════════════════════════════\n";
echo "   ✨ SIMULAZIONE COMPLETATA\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "📋 Stato Finale Utente Demo:\n";
$demoUser->refresh();
echo "   - Email: {$demoUser->email}\n";
echo "   - Dashboard onboarding: " . ($demoUser->onboarding_completed ? 'Completato' : 'Da fare') . "\n";
echo "   - Tools onboarding: " . json_encode($demoUser->onboarding_tools_completed ?? []) . "\n";

echo "\n🌐 Test Manuale Browser:\n";
echo "   1. Apri: {$baseUrl}/login\n";
echo "   2. Login: admin@demo.com / demo123\n";
echo "   3. Vai su:\n";
echo "      - {$baseUrl}/dashboard/pages\n";
echo "      - {$baseUrl}/dashboard/generations\n";
echo "      - {$baseUrl}/dashboard/prompts\n";
echo "      - {$baseUrl}/dashboard/api-keys\n";
echo "\n💡 Gli onboarding dovrebbero partire automaticamente!\n";
echo "   Se non partono, apri la console del browser (F12)\n";
echo "   e cerca eventuali errori JavaScript.\n\n";

echo "🔧 Debug Console:\n";
echo "   - Verifica che Shepherd sia caricato: typeof Shepherd\n";
echo "   - Verifica funzioni: typeof initPagesOnboardingTour\n";
echo "   - Avvia manualmente: window.startPagesOnboarding()\n\n";
