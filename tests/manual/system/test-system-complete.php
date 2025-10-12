<?php

/**
 * Test Completo Sistema Ainstein
 * Verifica tutte le funzionalità principali
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
use App\Models\ApiKey;
use App\Models\PlatformSetting;

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   🧪 TEST COMPLETO SISTEMA AINSTEIN\n";
echo "═══════════════════════════════════════════════════\n\n";

$errors = [];
$passed = 0;
$failed = 0;

// Test 1: Database Connection
echo "📊 Test 1: Connessione Database...\n";
try {
    DB::connection()->getPdo();
    echo "   ✅ Database connesso correttamente\n";
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore connessione database: " . $e->getMessage() . "\n";
    $errors[] = "Database Connection Failed";
    $failed++;
}

// Test 2: Super Admin Exists
echo "\n👤 Test 2: Verifica Super Admin...\n";
try {
    $admin = User::where('is_super_admin', true)->first();
    if ($admin) {
        echo "   ✅ Super Admin trovato: {$admin->email}\n";
        echo "      - Nome: {$admin->name}\n";
        echo "      - Attivo: " . ($admin->is_active ? 'Sì' : 'No') . "\n";
        $passed++;
    } else {
        echo "   ❌ Super Admin non trovato\n";
        $errors[] = "Super Admin not found";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Super Admin Check Failed";
    $failed++;
}

// Test 3: Tenants
echo "\n🏢 Test 3: Verifica Tenants...\n";
try {
    $tenants = Tenant::all();
    echo "   ✅ Tenants trovati: " . $tenants->count() . "\n";
    foreach ($tenants as $tenant) {
        echo "      - {$tenant->name} (Plan: {$tenant->plan_type}, Status: {$tenant->status})\n";
        echo "        Token: {$tenant->tokens_used_current}/{$tenant->tokens_monthly_limit}\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Tenants Check Failed";
    $failed++;
}

// Test 4: Users
echo "\n👥 Test 4: Verifica Users...\n";
try {
    $users = User::all();
    echo "   ✅ Users trovati: " . $users->count() . "\n";
    foreach ($users as $user) {
        $tenantName = $user->tenant ? $user->tenant->name : 'No Tenant';
        echo "      - {$user->email} (Tenant: {$tenantName}, Role: {$user->role})\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Users Check Failed";
    $failed++;
}

// Test 5: Pages
echo "\n📄 Test 5: Verifica Pages...\n";
try {
    $pages = Page::all();
    echo "   ✅ Pages trovate: " . $pages->count() . "\n";
    foreach ($pages as $page) {
        echo "      - {$page->url_path} (Keyword: {$page->keyword})\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Pages Check Failed";
    $failed++;
}

// Test 6: Prompts
echo "\n💬 Test 6: Verifica Prompts...\n";
try {
    $prompts = Prompt::all();
    echo "   ✅ Prompts trovati: " . $prompts->count() . "\n";
    foreach ($prompts as $prompt) {
        echo "      - {$prompt->name} (Category: {$prompt->category})\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Prompts Check Failed";
    $failed++;
}

// Test 7: Content Generations
echo "\n🤖 Test 7: Verifica Content Generations...\n";
try {
    $generations = ContentGeneration::all();
    echo "   ✅ Generations trovate: " . $generations->count() . "\n";
    foreach ($generations as $gen) {
        echo "      - Status: {$gen->status}, Tokens: {$gen->tokens_used}\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Generations Check Failed";
    $failed++;
}

// Test 8: Dashboard Controller
echo "\n🎛️  Test 8: Test Dashboard Controller...\n";
try {
    $demoUser = User::where('email', 'admin@demo.com')->first();
    if ($demoUser && $demoUser->tenant) {
        echo "   ✅ Demo user e tenant trovati\n";
        echo "      - User: {$demoUser->email}\n";
        echo "      - Tenant: {$demoUser->tenant->name}\n";

        // Test query problematiche
        $topPages = Page::where('tenant_id', $demoUser->tenant->id)
            ->withCount(['generations' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->get()
            ->filter(function ($page) {
                return $page->generations_count > 0;
            })
            ->sortByDesc('generations_count')
            ->take(5)
            ->values();

        echo "      - Top Pages Query: OK (risultati: " . $topPages->count() . ")\n";
        $passed++;
    } else {
        echo "   ❌ Demo user o tenant non trovati\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Dashboard Controller Test Failed";
    $failed++;
}

// Test 9: Platform Settings
echo "\n⚙️  Test 9: Verifica Platform Settings...\n";
try {
    $settings = PlatformSetting::first();
    if ($settings) {
        echo "   ✅ Platform Settings trovate\n";
        echo "      - OpenAI Model: " . ($settings->openai_model ?? 'Non configurato') . "\n";
        $passed++;
    } else {
        echo "   ⚠️  Platform Settings non trovate (normale per nuova installazione)\n";
        $passed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Platform Settings Check Failed";
    $failed++;
}

// Test 10: Routes
echo "\n🛣️  Test 10: Verifica Routes Principali...\n";
try {
    $routes = Route::getRoutes();
    $requiredRoutes = [
        'login',
        'dashboard',
        'admin.login',
        'admin.dashboard',
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

    if (count($foundRoutes) >= 3) {
        $passed++;
    } else {
        echo "   ⚠️  Alcune routes mancanti\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ❌ Errore: " . $e->getMessage() . "\n";
    $errors[] = "Routes Check Failed";
    $failed++;
}

// Summary
echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   📊 RIEPILOGO TEST\n";
echo "═══════════════════════════════════════════════════\n";
echo "✅ Test Passati: {$passed}\n";
echo "❌ Test Falliti: {$failed}\n";

if ($failed > 0) {
    echo "\n🚨 Errori riscontrati:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
}

$percentage = round(($passed / ($passed + $failed)) * 100, 2);
echo "\n📈 Percentuale Successo: {$percentage}%\n";

if ($percentage >= 90) {
    echo "\n🎉 Sistema PRONTO per l'uso!\n";
} elseif ($percentage >= 70) {
    echo "\n⚠️  Sistema PARZIALMENTE funzionante\n";
} else {
    echo "\n❌ Sistema ha PROBLEMI CRITICI\n";
}

echo "\n═══════════════════════════════════════════════════\n";
echo "\n🔗 URL Importanti:\n";
echo "   - Admin Panel: http://127.0.0.1:8080/admin/login\n";
echo "   - Tenant Login: http://127.0.0.1:8080/login\n";
echo "   - Dashboard: http://127.0.0.1:8080/dashboard\n";
echo "\n📧 Credenziali:\n";
echo "   Admin: admin@ainstein.com / admin123\n";
echo "   Demo: admin@demo.com / demo123\n";
echo "\n";
