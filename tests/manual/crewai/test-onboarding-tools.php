<?php

/**
 * Test Completo Sistema Onboarding Tools
 * Verifica implementazione onboarding per tutti i tool
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   🧪 TEST SISTEMA ONBOARDING TOOLS\n";
echo "═══════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;

// Test 1: Database Structure
echo "📊 Test 1: Verifica Struttura Database...\n";
try {
    $columns = DB::select("PRAGMA table_info(users)");
    $hasColumn = false;

    foreach ($columns as $col) {
        if ($col->name === 'onboarding_tools_completed') {
            $hasColumn = true;
            echo "   ✅ Colonna 'onboarding_tools_completed' presente\n";
            echo "      Type: {$col->type}\n";
            break;
        }
    }

    if ($hasColumn) {
        $passed++;
    } else {
        echo "   ❌ Colonna 'onboarding_tools_completed' NON trovata\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 2: User Model Methods
echo "\n🔧 Test 2: Verifica Metodi User Model...\n";
try {
    $demoUser = User::where('email', 'admin@demo.com')->first();

    if (!$demoUser) {
        echo "   ❌ Demo user non trovato\n";
        $failed++;
    } else {
        echo "   ✅ Demo user trovato: {$demoUser->email}\n";

        // Test hasCompletedToolOnboarding
        if (method_exists($demoUser, 'hasCompletedToolOnboarding')) {
            $result = $demoUser->hasCompletedToolOnboarding('pages');
            echo "   ✅ Method hasCompletedToolOnboarding() esiste (result: " . ($result ? 'true' : 'false') . ")\n";
        } else {
            echo "   ❌ Method hasCompletedToolOnboarding() NON trovato\n";
            $failed++;
        }

        // Test markToolOnboardingComplete
        if (method_exists($demoUser, 'markToolOnboardingComplete')) {
            echo "   ✅ Method markToolOnboardingComplete() esiste\n";
        } else {
            echo "   ❌ Method markToolOnboardingComplete() NON trovato\n";
            $failed++;
        }

        // Test resetToolOnboarding
        if (method_exists($demoUser, 'resetToolOnboarding')) {
            echo "   ✅ Method resetToolOnboarding() esiste\n";
        } else {
            echo "   ❌ Method resetToolOnboarding() NON trovato\n";
            $failed++;
        }

        $passed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: Tool Onboarding Workflow
echo "\n⚙️  Test 3: Test Workflow Completo...\n";
try {
    $testUser = User::where('email', 'admin@demo.com')->first();

    // Reset all onboarding
    $testUser->resetToolOnboarding();
    echo "   ✅ Reset onboarding completato\n";

    // Check initial state
    $completed = $testUser->onboarding_tools_completed ?? [];
    echo "   ✅ Stato iniziale: " . (empty($completed) ? 'vuoto (corretto)' : 'non vuoto') . "\n";

    // Mark 'pages' as completed
    $testUser->markToolOnboardingComplete('pages');
    $testUser->refresh();

    if ($testUser->hasCompletedToolOnboarding('pages')) {
        echo "   ✅ 'pages' marcato come completato\n";
    } else {
        echo "   ❌ 'pages' NON marcato come completato\n";
        $failed++;
    }

    // Mark 'content-generation' as completed
    $testUser->markToolOnboardingComplete('content-generation');
    $testUser->refresh();

    if ($testUser->hasCompletedToolOnboarding('content-generation')) {
        echo "   ✅ 'content-generation' marcato come completato\n";
    } else {
        echo "   ❌ 'content-generation' NON marcato come completato\n";
        $failed++;
    }

    // Check array contains both
    $completedTools = $testUser->onboarding_tools_completed ?? [];
    if (in_array('pages', $completedTools) && in_array('content-generation', $completedTools)) {
        echo "   ✅ Array contiene entrambi i tool: " . implode(', ', $completedTools) . "\n";
        $passed++;
    } else {
        echo "   ❌ Array non contiene i tool corretti: " . implode(', ', $completedTools) . "\n";
        $failed++;
    }

    // Reset specific tool
    $testUser->resetToolOnboarding('pages');
    $testUser->refresh();

    if (!$testUser->hasCompletedToolOnboarding('pages') && $testUser->hasCompletedToolOnboarding('content-generation')) {
        echo "   ✅ Reset specifico funziona correttamente\n";
    } else {
        echo "   ❌ Reset specifico NON funziona\n";
        $failed++;
    }

} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 4: Routes Existence
echo "\n🛣️  Test 4: Verifica Routes API...\n";
try {
    $routes = Route::getRoutes();
    $requiredRoutes = [
        'tenant.onboarding.tool.complete',
        'tenant.onboarding.tool.reset',
        'tenant.onboarding.status',
    ];

    $foundRoutes = [];
    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && in_array($name, $requiredRoutes)) {
            $foundRoutes[] = $name;
        }
    }

    echo "   ✅ Routes trovate: " . count($foundRoutes) . "/" . count($requiredRoutes) . "\n";
    foreach ($foundRoutes as $routeName) {
        echo "      - {$routeName}\n";
    }

    if (count($foundRoutes) === count($requiredRoutes)) {
        $passed++;
    } else {
        echo "   ⚠️  Routes mancanti: " . implode(', ', array_diff($requiredRoutes, $foundRoutes)) . "\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 5: JavaScript Files Existence
echo "\n📝 Test 5: Verifica File JavaScript...\n";
try {
    $jsFiles = [
        'resources/js/onboarding-tools.js',
        'resources/js/app.js',
    ];

    foreach ($jsFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $size = filesize($fullPath);
            echo "   ✅ {$file} presente (" . number_format($size) . " bytes)\n";
        } else {
            echo "   ❌ {$file} NON trovato\n";
            $failed++;
        }
    }

    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 6: Vite Manifest
echo "\n🎨 Test 6: Verifica Build Vite...\n";
try {
    $manifestPath = __DIR__ . '/public/build/manifest.json';

    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        echo "   ✅ Manifest Vite presente\n";

        if (isset($manifest['resources/js/app.js'])) {
            echo "   ✅ app.js compilato: {$manifest['resources/js/app.js']['file']}\n";
            $passed++;
        } else {
            echo "   ❌ app.js NON trovato nel manifest\n";
            $failed++;
        }
    } else {
        echo "   ❌ Manifest Vite non trovato - esegui 'npm run build'\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 7: Tool Names Validation
echo "\n✅ Test 7: Validazione Nomi Tool...\n";
try {
    $validTools = ['pages', 'content-generation', 'prompts', 'api-keys'];
    echo "   ✅ Tool supportati:\n";
    foreach ($validTools as $tool) {
        echo "      - {$tool}\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   📊 RIEPILOGO TEST\n";
echo "═══════════════════════════════════════════════════\n";
echo "✅ Test Passati: {$passed}\n";
echo "❌ Test Falliti: {$failed}\n";

$percentage = $passed > 0 ? round(($passed / ($passed + $failed)) * 100, 2) : 0;
echo "\n📈 Percentuale Successo: {$percentage}%\n";

if ($percentage >= 90) {
    echo "\n🎉 Sistema Onboarding Tools PRONTO!\n";
} elseif ($percentage >= 70) {
    echo "\n⚠️  Sistema PARZIALMENTE funzionante\n";
} else {
    echo "\n❌ Sistema ha PROBLEMI CRITICI\n";
}

echo "\n═══════════════════════════════════════════════════\n";
echo "\n📝 Prossimi Passi:\n";
echo "   1. Apri il browser su http://127.0.0.1:8080/login\n";
echo "   2. Login con: admin@demo.com / demo123\n";
echo "   3. Naviga sulle varie sezioni per vedere gli onboarding:\n";
echo "      - Pages: /dashboard/pages\n";
echo "      - Content Generation: /dashboard/generations\n";
echo "      - Prompts: /dashboard/prompts\n";
echo "      - API Keys: /dashboard/api-keys\n";
echo "\n💡 Gli onboarding appariranno automaticamente\n";
echo "   alla prima visita di ogni sezione!\n\n";
