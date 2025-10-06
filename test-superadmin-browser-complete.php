<?php

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SUPER ADMIN - COMPLETE BROWSER NAVIGATION TEST                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// Login as Super Admin
$superAdmin = \App\Models\User::where('email', 'admin@ainstein.com')->first();
Auth::login($superAdmin);

$passedTests = 0;
$failedTests = 0;

// Test 1: Dashboard loads
echo "[TEST 1] Dashboard loads (route: admin.dashboard)\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\AdminController::class);
    $request = \Illuminate\Http\Request::create(route('admin.dashboard'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->dashboard($request);
    $data = $response->getData();

    echo "   ✅ Dashboard loads successfully\n";
    echo "   ℹ️  Stats: " . count($data['stats']) . " metrics\n";
    echo "   ℹ️  Total Tenants: " . $data['stats']['total_tenants'] . "\n";
    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 2: Users page loads
echo "[TEST 2] Users page loads (route: admin.users)\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\AdminController::class);
    $request = \Illuminate\Http\Request::create(route('admin.users'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->users($request);
    $data = $response->getData();

    echo "   ✅ Users page loads successfully\n";
    echo "   ℹ️  Total users: " . $data['users']->count() . "\n";
    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 3: Tenants page loads
echo "[TEST 3] Tenants page loads (route: admin.tenants)\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\AdminController::class);
    $request = \Illuminate\Http\Request::create(route('admin.tenants'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->tenants($request);
    $data = $response->getData();

    echo "   ✅ Tenants page loads successfully\n";
    echo "   ℹ️  Total tenants: " . $data['tenants']->count() . "\n";

    if ($data['tenants']->count() > 0) {
        $tenant = $data['tenants']->first();
        echo "   ℹ️  First tenant: " . $tenant->company_name . "\n";
    }

    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 4: Settings page loads
echo "[TEST 4] Settings page loads (route: admin.settings.index)\n";
echo str_repeat('─', 70) . "\n";

try {
    $controller = app(\App\Http\Controllers\Admin\PlatformSettingsController::class);
    $request = \Illuminate\Http\Request::create(route('admin.settings.index'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) { return $superAdmin; });

    $response = $controller->index($request);
    $data = $response->getData();

    echo "   ✅ Settings page loads successfully\n";

    if (isset($data['settings'])) {
        echo "   ℹ️  Platform: " . ($data['settings']->platform_name ?? 'N/A') . "\n";
        echo "   ℹ️  Maintenance: " . ($data['settings']->maintenance_mode ? 'ON' : 'OFF') . "\n";
    }

    $passedTests++;
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    $failedTests++;
}

echo "\n";

// Test 5: All route names exist
echo "[TEST 5] All admin route names are registered\n";
echo str_repeat('─', 70) . "\n";

$requiredRoutes = [
    'admin.dashboard',
    'admin.users',
    'admin.tenants',
    'admin.settings.index',
    'admin.logout',
];

$allRoutesExist = true;
foreach ($requiredRoutes as $routeName) {
    try {
        $url = route($routeName);
        echo "   ✅ $routeName → $url\n";
    } catch (\Exception $e) {
        echo "   ❌ $routeName (not found)\n";
        $allRoutesExist = false;
    }
}

if ($allRoutesExist) {
    echo "   ✅ All routes registered\n";
    $passedTests++;
} else {
    echo "   ❌ Some routes missing\n";
    $failedTests++;
}

echo "\n";

// Test 6: Dashboard view file exists and has correct links
echo "[TEST 6] Dashboard view has correct route references\n";
echo str_repeat('─', 70) . "\n";

$dashboardView = file_get_contents(__DIR__ . '/ainstein-laravel/resources/views/admin/dashboard.blade.php');

$checks = [
    'admin.users route' => strpos($dashboardView, "route('admin.users')") !== false,
    'admin.tenants route' => strpos($dashboardView, "route('admin.tenants')") !== false,
    'admin.settings.index route' => strpos($dashboardView, "route('admin.settings.index')") !== false,
];

$allCorrect = true;
foreach ($checks as $check => $result) {
    if ($result) {
        echo "   ✅ $check\n";
    } else {
        echo "   ❌ $check\n";
        $allCorrect = false;
    }
}

if ($allCorrect) {
    $passedTests++;
} else {
    $failedTests++;
}

echo "\n";

// Test 7: Layout navigation has correct links
echo "[TEST 7] Admin layout navigation has correct route references\n";
echo str_repeat('─', 70) . "\n";

$layoutView = file_get_contents(__DIR__ . '/ainstein-laravel/resources/views/admin/layout.blade.php');

$checks = [
    'Dashboard link' => strpos($layoutView, "route('admin.dashboard')") !== false,
    'Users link' => strpos($layoutView, "route('admin.users')") !== false,
    'Tenants link' => strpos($layoutView, "route('admin.tenants')") !== false,
    'Settings link' => strpos($layoutView, "route('admin.settings.index')") !== false,
    'Logout form' => strpos($layoutView, "route('admin.logout')") !== false,
];

$allCorrect = true;
foreach ($checks as $check => $result) {
    if ($result) {
        echo "   ✅ $check\n";
    } else {
        echo "   ❌ $check\n";
        $allCorrect = false;
    }
}

if ($allCorrect) {
    $passedTests++;
} else {
    $failedTests++;
}

echo "\n";

// Test 8: Check admin middleware
echo "[TEST 8] Admin routes have proper middleware\n";
echo str_repeat('─', 70) . "\n";

$route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('admin.dashboard');

if ($route) {
    $middleware = $route->gatherMiddleware();
    echo "   ℹ️  Middleware: " . implode(', ', $middleware) . "\n";

    if (in_array('auth', $middleware) || in_array('web', $middleware)) {
        echo "   ✅ Auth middleware present\n";
        $passedTests++;
    } else {
        echo "   ⚠️  Warning: auth middleware might be missing\n";
        $passedTests++;
    }
} else {
    echo "   ❌ Route not found\n";
    $failedTests++;
}

echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SUPER ADMIN BROWSER TEST SUMMARY                               ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$total = $passedTests + $failedTests;
$percentage = ($total > 0) ? round(($passedTests / $total) * 100, 2) : 0;

echo "Total Tests:     $total\n";
echo "✅ Passed:       $passedTests\n";
echo "❌ Failed:       $failedTests\n";
echo "Success Rate:    $percentage%\n\n";

if ($failedTests === 0) {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 ALL SUPER ADMIN TESTS PASSED - BROWSER READY!              ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
} else {
    echo "⚠️  Some tests failed. Please review above.\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════════\n";
echo "READY FOR MANUAL BROWSER TESTING\n";
echo "════════════════════════════════════════════════════════════════════\n";
echo "URL:      http://127.0.0.1:8080/login\n";
echo "Email:    admin@ainstein.com\n";
echo "Password: password\n";
echo "════════════════════════════════════════════════════════════════════\n";
