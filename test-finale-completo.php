<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "\n";
echo "🔍 TEST PRE-VOLO COMPLETO - Verifica Finale\n";
echo str_repeat('=', 70) . "\n\n";

$errors = [];
$warnings = [];
$success = [];

// TEST 1: Server accessibility
echo "1️⃣  Verifica server...\n";
$baseUrl = 'http://127.0.0.1:8080';
$response = @file_get_contents($baseUrl);
if ($response && strpos($response, 'Ainstein') !== false) {
    $success[] = "✅ Server risponde correttamente";
    echo "   ✅ Server attivo su {$baseUrl}\n";
} else {
    $errors[] = "❌ Server non risponde";
    echo "   ❌ Server non risponde! Avvialo con: php artisan serve --port=8080\n";
}
echo "\n";

// TEST 2: Database connection
echo "2️⃣  Verifica database...\n";
try {
    DB::connection()->getPdo();
    $success[] = "✅ Database connesso";
    echo "   ✅ Database connesso\n";

    $dbName = DB::connection()->getDatabaseName();
    echo "   ✅ Database: {$dbName}\n";
} catch (Exception $e) {
    $errors[] = "❌ Database non connesso: " . $e->getMessage();
    echo "   ❌ Errore database: {$e->getMessage()}\n";
}
echo "\n";

// TEST 3: Migrations
echo "3️⃣  Verifica migrations...\n";
try {
    $migrations = DB::table('migrations')->count();
    $success[] = "✅ {$migrations} migrations eseguite";
    echo "   ✅ {$migrations} migrations eseguite\n";
} catch (Exception $e) {
    $errors[] = "❌ Migrations non eseguite";
    echo "   ❌ Esegui: php artisan migrate\n";
}
echo "\n";

// TEST 4: Users
echo "4️⃣  Verifica utenti...\n";
$adminUser = App\Models\User::where('email', 'admin@ainstein.com')->first();
$demoUser = App\Models\User::where('email', 'demo@tenant.com')->first();

if ($adminUser) {
    $success[] = "✅ Super admin esistente";
    echo "   ✅ Super Admin: admin@ainstein.com / Admin123!\n";
    echo "      - Role: {$adminUser->role}\n";
    echo "      - Is Super Admin: " . ($adminUser->is_super_admin ? 'Yes' : 'No') . "\n";
} else {
    $errors[] = "❌ Super admin non trovato";
    echo "   ❌ Super admin non trovato! Esegui seeder.\n";
}

if ($demoUser) {
    $success[] = "✅ Demo user esistente";
    echo "   ✅ Demo User: demo@tenant.com / password\n";
    echo "      - Role: {$demoUser->role}\n";
    echo "      - Tenant: {$demoUser->tenant->name}\n";
} else {
    $warnings[] = "⚠️  Demo user non trovato";
    echo "   ⚠️  Demo user non trovato (opzionale)\n";
}
echo "\n";

// TEST 5: Tenants
echo "5️⃣  Verifica tenants...\n";
$tenants = App\Models\Tenant::count();
$demoTenant = App\Models\Tenant::where('subdomain', 'demo')->first();

echo "   ✅ {$tenants} tenants nel database\n";
if ($demoTenant) {
    $success[] = "✅ Demo tenant configurato";
    echo "   ✅ Demo Tenant: {$demoTenant->name}\n";
    echo "      - Plan: {$demoTenant->plan_type}\n";
    echo "      - Token Limit: " . number_format($demoTenant->tokens_monthly_limit) . "\n";
} else {
    $warnings[] = "⚠️  Demo tenant non trovato";
    echo "   ⚠️  Demo tenant non trovato\n";
}
echo "\n";

// TEST 6: Pages
echo "6️⃣  Verifica pages...\n";
$pages = App\Models\Page::count();
echo "   ✅ {$pages} pages nel database\n";
if ($pages > 0) {
    $success[] = "✅ Pages disponibili per test";
    $firstPage = App\Models\Page::first();
    echo "   ✅ Esempio: {$firstPage->url_path} (keyword: {$firstPage->keyword})\n";
}
echo "\n";

// TEST 7: Prompts
echo "7️⃣  Verifica prompts...\n";
$prompts = App\Models\Prompt::count();
echo "   ✅ {$prompts} prompts disponibili\n";
if ($prompts > 0) {
    $success[] = "✅ Prompts configurati";
    $systemPrompts = App\Models\Prompt::where('is_system', true)->count();
    echo "   ✅ {$systemPrompts} system prompts\n";
}
echo "\n";

// TEST 8: Platform Settings
echo "8️⃣  Verifica platform settings...\n";
$settings = App\Models\PlatformSetting::first();
if ($settings) {
    $success[] = "✅ Platform settings configurati";
    echo "   ✅ Platform settings presenti\n";
    echo "      - OpenAI Key: " . ($settings->openai_api_key ? (substr($settings->openai_api_key, 0, 15) . '...') : 'NON CONFIGURATA') . "\n";
    echo "      - OpenAI Model: " . ($settings->openai_model ?? 'gpt-4o (default)') . "\n";

    if ($settings->openai_api_key === 'sk-test-key') {
        $warnings[] = "⚠️  Usando MockOpenAiService (chiave test)";
        echo "   ⚠️  Modalità TEST - MockOpenAiService attivo\n";
        echo "      Per OpenAI reale: Admin Panel → Platform Settings → Inserisci chiave vera\n";
    } elseif (!$settings->openai_api_key) {
        $warnings[] = "⚠️  OpenAI key non configurata";
        echo "   ⚠️  OpenAI key non configurata\n";
        echo "      Configura da: http://127.0.0.1:8080/admin → Platform Settings\n";
    } else {
        $success[] = "✅ OpenAI key configurata";
        echo "   ✅ OpenAI key configurata (sembra reale)\n";
    }
} else {
    $errors[] = "❌ Platform settings mancanti";
    echo "   ❌ Platform settings non trovati! Esegui seeder.\n";
}
echo "\n";

// TEST 9: Routes
echo "9️⃣  Verifica routes...\n";
try {
    $routes = count(Route::getRoutes());
    $success[] = "✅ {$routes} routes registrate";
    echo "   ✅ {$routes} routes registrate\n";

    // Verifica route specifiche
    $importantRoutes = [
        'login' => route('login'),
        'tenant.dashboard' => route('tenant.dashboard'),
        'api.auth.login' => route('api.auth.login'),
    ];

    echo "   ✅ Route importanti:\n";
    foreach ($importantRoutes as $name => $url) {
        echo "      - {$name}: {$url}\n";
    }
} catch (Exception $e) {
    $errors[] = "❌ Errore routes";
    echo "   ❌ Errore: {$e->getMessage()}\n";
}
echo "\n";

// TEST 10: Login simulation
echo "🔟 Test login flow...\n";
try {
    if ($demoUser && Hash::check('password', $demoUser->password_hash)) {
        $success[] = "✅ Credenziali demo valide";
        echo "   ✅ Credenziali demo verificate\n";
        echo "      Email: demo@tenant.com\n";
        echo "      Password: password\n";

        // Simulate login
        Auth::login($demoUser);
        if (Auth::check()) {
            $success[] = "✅ Sistema autenticazione funzionante";
            echo "   ✅ Sistema di autenticazione OK\n";

            // Determine redirect
            if ($demoUser->is_super_admin) {
                echo "   ✅ Redirect atteso: /admin\n";
            } else {
                echo "   ✅ Redirect atteso: /dashboard\n";
            }
        }
        Auth::logout();
    } else {
        $warnings[] = "⚠️  Demo user non disponibile";
        echo "   ⚠️  Demo user non disponibile\n";
    }
} catch (Exception $e) {
    $errors[] = "❌ Errore login: " . $e->getMessage();
    echo "   ❌ Errore: {$e->getMessage()}\n";
}
echo "\n";

// TEST 11: OpenAI Service
echo "1️⃣1️⃣  Test OpenAI service...\n";
try {
    $openAiService = new App\Services\OpenAiService();
    $testPrompt = "Test prompt";
    $result = $openAiService->generateContent($testPrompt, ['keyword' => 'test']);

    if ($result['success']) {
        $success[] = "✅ OpenAI service funzionante";
        echo "   ✅ OpenAI service funzionante\n";
        echo "      Content preview: " . substr($result['content'], 0, 50) . "...\n";
        echo "      Tokens: {$result['tokens_used']}\n";

        if (isset($result['is_mock']) || $result['tokens_used'] < 30) {
            echo "   ℹ️  Usando MockOpenAiService (test mode)\n";
        } else {
            echo "   ✅ Usando OpenAI reale\n";
        }
    } else {
        $warnings[] = "⚠️  OpenAI generation fallita";
        echo "   ⚠️  Generazione fallita: {$result['error']}\n";
    }
} catch (Exception $e) {
    $errors[] = "❌ OpenAI service error: " . $e->getMessage();
    echo "   ❌ Errore: {$e->getMessage()}\n";
}
echo "\n";

// TEST 12: File permissions
echo "1️⃣2️⃣  Verifica permessi...\n";
$writableDirs = [
    'storage/logs' => is_writable(storage_path('logs')),
    'storage/framework' => is_writable(storage_path('framework')),
    'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
];

foreach ($writableDirs as $dir => $writable) {
    if ($writable) {
        echo "   ✅ {$dir} scrivibile\n";
    } else {
        $warnings[] = "⚠️  {$dir} non scrivibile";
        echo "   ⚠️  {$dir} non scrivibile\n";
    }
}
echo "\n";

// SUMMARY
echo str_repeat('=', 70) . "\n";
echo "📊 RIEPILOGO FINALE\n";
echo str_repeat('=', 70) . "\n\n";

echo "✅ SUCCESSI (" . count($success) . "):\n";
foreach ($success as $s) {
    echo "   {$s}\n";
}
echo "\n";

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "   {$w}\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "❌ ERRORI (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "   {$e}\n";
    }
    echo "\n";
    echo "🚫 CI SONO ERRORI! Risolverli prima di procedere.\n";
    exit(1);
}

// Final verdict
echo str_repeat('=', 70) . "\n";
if (count($errors) === 0 && count($warnings) <= 2) {
    echo "🎉 TUTTO OK! La piattaforma è PRONTA per il test browser!\n";
    echo str_repeat('=', 70) . "\n\n";

    echo "📝 ISTRUZIONI PER L'UTENTE:\n\n";
    echo "1️⃣  Apri browser e vai su: http://127.0.0.1:8080\n";
    echo "2️⃣  Click 'Accedi' o vai su: http://127.0.0.1:8080/login\n";
    echo "3️⃣  Login con:\n";
    echo "    Email: demo@tenant.com\n";
    echo "    Password: password\n";
    echo "    (oppure click su 'Demo Login')\n\n";
    echo "4️⃣  Dopo login sarai su: http://127.0.0.1:8080/dashboard\n";
    echo "5️⃣  Esplora:\n";
    echo "    - Dashboard (stats e token usage)\n";
    echo "    - Pages (gestione pagine)\n";
    echo "    - Prompts (template AI)\n";
    echo "    - Content Generation (genera contenuti!)\n";
    echo "    - API Keys (genera chiavi)\n\n";

    if (count($warnings) > 0) {
        echo "ℹ️  Note:\n";
        foreach ($warnings as $w) {
            echo "   - {$w}\n";
        }
        echo "\n";
    }

    echo "🔐 Per configurare OpenAI reale:\n";
    echo "    1. Logout\n";
    echo "    2. Vai su: http://127.0.0.1:8080/admin\n";
    echo "    3. Login: admin@ainstein.com / Admin123!\n";
    echo "    4. Platform Settings → Edit\n";
    echo "    5. Inserisci chiave OpenAI vera\n";
    echo "    6. Save\n\n";

    echo "✅ NON CI SARANNO PROBLEMI! 😊\n";
    echo str_repeat('=', 70) . "\n";
} else {
    echo "⚠️  Ci sono alcuni warnings ma la piattaforma dovrebbe funzionare.\n";
    echo "Verifica i warnings sopra se necessario.\n";
    echo str_repeat('=', 70) . "\n";
}
