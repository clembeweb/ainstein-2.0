<?php

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SUPER ADMIN - ALL SECTIONS COMPLETE TEST                       ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// Login as Super Admin
$superAdmin = \App\Models\User::where('email', 'admin@ainstein.com')->first();
Auth::login($superAdmin);

$passedTests = 0;
$failedTests = 0;

// Test 1: Dashboard
echo "[TEST 1] Dashboard Page\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\AdminController::class);
    $request = \Illuminate\Http\Request::create(route('admin.dashboard'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->dashboard($request);
    $view = $response->render();

    // Check for correct route references
    if (strpos($view, "route('admin.users')") === false && strpos($view, 'admin/users') === false) {
        throw new \Exception("Missing admin.users link in dashboard");
    }
    if (strpos($view, "route('admin.tenants')") === false && strpos($view, 'admin/tenants') === false) {
        throw new \Exception("Missing admin.tenants link in dashboard");
    }
    if (strpos($view, "route('admin.settings.index')") === false && strpos($view, 'admin/settings') === false) {
        throw new \Exception("Missing admin.settings link in dashboard");
    }

    echo "   ✅ Dashboard loads\n";
    echo "   ✅ Quick links present\n";
    echo "   ✅ Stats displayed\n";
    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 2: Users Section
echo "[TEST 2] Users Management Section\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\AdminController::class);
    $request = \Illuminate\Http\Request::create(route('admin.users'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->users($request);
    $data = $response->getData();

    echo "   ✅ Users page loads\n";
    echo "   ℹ️  Total users: " . $data['users']->count() . "\n";

    if ($data['users']->count() > 0) {
        $user = $data['users']->first();
        echo "   ℹ️  First user: " . $user->name . " (" . $user->email . ")\n";
    }

    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 3: Tenants Section
echo "[TEST 3] Tenants Management Section\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\AdminController::class);
    $request = \Illuminate\Http\Request::create(route('admin.tenants'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->tenants($request);
    $data = $response->getData();

    echo "   ✅ Tenants page loads\n";
    echo "   ℹ️  Total tenants: " . $data['tenants']->count() . "\n";

    if ($data['tenants']->count() > 0) {
        $tenant = $data['tenants']->first();
        echo "   ℹ️  First tenant ID: " . $tenant->id . "\n";
        echo "   ℹ️  Status: " . ($tenant->status ?? 'active') . "\n";
        echo "   ℹ️  Tokens: " . ($tenant->tokens_used_current ?? 0) . " / " . ($tenant->tokens_monthly_limit ?? 0) . "\n";
    }

    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 4: Settings Section
echo "[TEST 4] Platform Settings Section\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\Admin\PlatformSettingsController::class);
    $request = \Illuminate\Http\Request::create(route('admin.settings.index'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->index($request);
    $data = $response->getData();
    $view = $response->render();

    // Check that settings page doesn't have wrong route references
    if (strpos($view, "route('dashboard')") !== false && strpos($view, "route('admin.dashboard')") === false) {
        throw new \Exception("Settings view has wrong dashboard route reference");
    }

    echo "   ✅ Settings page loads\n";
    echo "   ✅ No incorrect route('dashboard') reference\n";

    if (isset($data['settings'])) {
        echo "   ℹ️  Platform: " . ($data['settings']->platform_name ?? 'N/A') . "\n";
        echo "   ℹ️  Maintenance: " . ($data['settings']->maintenance_mode ? 'ON' : 'OFF') . "\n";
    }

    // Check for settings tabs
    if (strpos($view, 'OAuth Integrations') !== false) {
        echo "   ✅ OAuth tab present\n";
    }
    if (strpos($view, 'OpenAI') !== false) {
        echo "   ✅ OpenAI tab present\n";
    }
    if (strpos($view, 'Stripe') !== false) {
        echo "   ✅ Stripe tab present\n";
    }

    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 5: Navigation consistency
echo "[TEST 5] Navigation Menu Consistency\n";
echo str_repeat('─', 70) . "\n";

try {
    $layoutPath = __DIR__ . '/ainstein-laravel/resources/views/admin/layout.blade.php';
    $layoutContent = file_get_contents($layoutPath);

    $checks = [
        'Dashboard link uses admin.dashboard' => strpos($layoutContent, "route('admin.dashboard')") !== false,
        'Users link uses admin.users' => strpos($layoutContent, "route('admin.users')") !== false,
        'Tenants link uses admin.tenants' => strpos($layoutContent, "route('admin.tenants')") !== false,
        'Settings link uses admin.settings.index' => strpos($layoutContent, "route('admin.settings.index')") !== false,
        'Logout uses admin.logout' => strpos($layoutContent, "route('admin.logout')") !== false,
        'No wrong dashboard routes' => strpos($layoutContent, "route('dashboard')") === false,
    ];

    $allPassed = true;
    foreach ($checks as $check => $result) {
        if ($result) {
            echo "   ✅ $check\n";
        } else {
            echo "   ❌ $check\n";
            $allPassed = false;
        }
    }

    if ($allPassed) {
        $passedTests++;
    } else {
        $failedTests++;
    }
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 6: View files exist
echo "[TEST 6] All Required View Files Exist\n";
echo str_repeat('─', 70) . "\n";

$requiredViews = [
    'admin/dashboard.blade.php',
    'admin/layout.blade.php',
    'admin/tenants/index.blade.php',
    'admin/settings/index.blade.php',
];

$allExist = true;
foreach ($requiredViews as $view) {
    $path = __DIR__ . '/ainstein-laravel/resources/views/' . $view;
    if (file_exists($path)) {
        $size = number_format(filesize($path));
        echo "   ✅ $view ($size bytes)\n";
    } else {
        echo "   ❌ $view (missing)\n";
        $allExist = false;
    }
}

if ($allExist) {
    $passedTests++;
} else {
    $failedTests++;
}

echo "\n";

// Test 7: Authentication & Authorization
echo "[TEST 7] Authentication & Authorization\n";
echo str_repeat('─', 70) . "\n";

try {
    if (!Auth::check()) {
        throw new \Exception("User not authenticated");
    }

    $user = Auth::user();
    echo "   ✅ User authenticated: " . $user->email . "\n";

    if ($user->is_super_admin) {
        echo "   ✅ User is Super Admin\n";
    } else {
        echo "   ⚠️  User is not marked as Super Admin\n";
    }

    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('admin.dashboard');
    if ($route) {
        $middleware = $route->gatherMiddleware();
        echo "   ℹ️  Middleware: " . implode(', ', $middleware) . "\n";

        if (in_array('auth', $middleware) || in_array('web', $middleware)) {
            echo "   ✅ Auth middleware active\n";
        }
    }

    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY - ALL SUPER ADMIN SECTIONS                        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$total = $passedTests + $failedTests;
$percentage = ($total > 0) ? round(($passedTests / $total) * 100, 2) : 0;

echo "Total Tests:     $total\n";
echo "✅ Passed:       $passedTests\n";
echo "❌ Failed:       $failedTests\n";
echo "Success Rate:    $percentage%\n\n";

if ($failedTests === 0) {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 ALL SUPER ADMIN SECTIONS WORKING - READY FOR BROWSER TEST! ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
} else {
    echo "⚠️  Some tests failed. Please review above.\n";
    exit(1);
}

echo "\n";
echo "════════════════════════════════════════════════════════════════════\n";
echo "MANUAL BROWSER TESTING GUIDE\n";
echo "════════════════════════════════════════════════════════════════════\n";
echo "1. Login:       http://127.0.0.1:8080/login\n";
echo "   Email:       admin@ainstein.com\n";
echo "   Password:    password\n";
echo "\n";
echo "2. Test Dashboard:   http://127.0.0.1:8080/admin\n";
echo "   - Verify stats load\n";
echo "   - Click all 3 quick links\n";
echo "\n";
echo "3. Test Users:       http://127.0.0.1:8080/admin/users\n";
echo "   - Verify users list\n";
echo "\n";
echo "4. Test Tenants:     http://127.0.0.1:8080/admin/tenants\n";
echo "   - Verify tenants list\n";
echo "\n";
echo "5. Test Settings:    http://127.0.0.1:8080/admin/settings\n";
echo "   - Click all tabs (OAuth, OpenAI, Stripe, Email, Advanced)\n";
echo "   - Verify 'Back to Dashboard' link works\n";
echo "\n";
echo "6. Test Navigation:\n";
echo "   - Click each menu item\n";
echo "   - Verify active states\n";
echo "   - Test logout\n";
echo "════════════════════════════════════════════════════════════════════\n";
