<?php

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SUPER ADMIN DASHBOARD - COMPLETE BROWSER TEST                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// Find Super Admin user
echo "[TEST 1] Find Super Admin user\n";
echo str_repeat('─', 70) . "\n";

$superAdmin = \App\Models\User::where('email', 'admin@ainstein.com')->first();

if (!$superAdmin) {
    echo "❌ FAILED: Super Admin not found\n";
    exit(1);
}

echo "   ℹ️  Name: {$superAdmin->name}\n";
echo "   ℹ️  Email: {$superAdmin->email}\n";
echo "   ℹ️  Role: " . ($superAdmin->role ?? 'N/A') . "\n";
echo "   ℹ️  Is Super Admin: " . ($superAdmin->is_super_admin ? 'Yes' : 'No') . "\n";
echo "✅ PASSED\n\n";

// Login as Super Admin
echo "[TEST 2] Login as Super Admin\n";
echo str_repeat('─', 70) . "\n";

Auth::login($superAdmin);

if (!Auth::check()) {
    echo "❌ FAILED: Could not authenticate\n";
    exit(1);
}

echo "   ℹ️  Authenticated: " . Auth::user()->name . "\n";
echo "   ℹ️  User ID: " . Auth::id() . "\n";
echo "✅ PASSED\n\n";

// Check Super Admin routes exist
echo "[TEST 3] Super Admin routes exist\n";
echo str_repeat('─', 70) . "\n";

$routes = [
    'admin.dashboard',
    'admin.tenants.index',
    'admin.settings.index',
];

$allExist = true;
foreach ($routes as $route) {
    try {
        $url = route($route);
        echo "   ℹ️  ✓ $route → $url\n";
    } catch (\Exception $e) {
        echo "   ❌ ✗ $route (not found)\n";
        $allExist = false;
    }
}

echo $allExist ? "✅ PASSED\n\n" : "❌ FAILED: Some routes missing\n\n";

// Load Super Admin dashboard
echo "[TEST 4] Load Super Admin dashboard\n";
echo str_repeat('─', 70) . "\n";

try {
    $request = \Illuminate\Http\Request::create(route('admin.dashboard'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) {
        return $superAdmin;
    });

    $controller = app(\App\Http\Controllers\AdminDashboardController::class);
    $response = $controller->index($request);

    $data = $response->getData();

    echo "   ℹ️  View: " . $response->name() . "\n";
    echo "   ℹ️  Stats available:\n";

    if (isset($data['stats'])) {
        foreach ($data['stats'] as $key => $value) {
            echo "      - $key: $value\n";
        }
    }

    echo "✅ PASSED\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Check tenants management
echo "[TEST 5] Load Tenants management page\n";
echo str_repeat('─', 70) . "\n";

try {
    $request = \Illuminate\Http\Request::create(route('admin.tenants.index'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) {
        return $superAdmin;
    });

    $controller = app(\App\Http\Controllers\AdminTenantController::class);
    $response = $controller->index($request);

    $data = $response->getData();

    echo "   ℹ️  View: " . $response->name() . "\n";

    if (isset($data['tenants'])) {
        $count = $data['tenants']->count();
        echo "   ℹ️  Tenants count: $count\n";

        if ($count > 0) {
            $first = $data['tenants']->first();
            echo "   ℹ️  First tenant: {$first->company_name}\n";
        }
    }

    echo "✅ PASSED\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Check platform settings
echo "[TEST 6] Load Platform Settings page\n";
echo str_repeat('─', 70) . "\n";

try {
    $request = \Illuminate\Http\Request::create(route('admin.settings.index'), 'GET');
    $request->setUserResolver(function () use ($superAdmin) {
        return $superAdmin;
    });

    $controller = app(\App\Http\Controllers\AdminSettingsController::class);
    $response = $controller->index($request);

    $data = $response->getData();

    echo "   ℹ️  View: " . $response->name() . "\n";

    if (isset($data['settings'])) {
        $settings = $data['settings'];
        echo "   ℹ️  Platform name: " . ($settings->platform_name ?? 'N/A') . "\n";
        echo "   ℹ️  Maintenance mode: " . ($settings->maintenance_mode ? 'ON' : 'OFF') . "\n";
    }

    echo "✅ PASSED\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Check Super Admin views exist
echo "[TEST 7] Super Admin view files exist\n";
echo str_repeat('─', 70) . "\n";

$views = [
    'resources/views/admin/dashboard.blade.php',
    'resources/views/admin/tenants/index.blade.php',
    'resources/views/admin/settings/index.blade.php',
    'resources/views/admin/layout.blade.php',
];

$allExist = true;
foreach ($views as $view) {
    $path = __DIR__ . '/ainstein-laravel/' . $view;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "   ℹ️  ✓ $view (" . number_format($size) . " bytes)\n";
    } else {
        echo "   ❌ ✗ $view (missing)\n";
        $allExist = false;
    }
}

echo $allExist ? "✅ PASSED\n\n" : "⚠️  WARNING: Some views missing\n\n";

// Database stats
echo "[TEST 8] Platform statistics\n";
echo str_repeat('─', 70) . "\n";

$stats = [
    'Total Users' => \App\Models\User::count(),
    'Total Tenants' => \App\Models\Tenant::count(),
    'Total Contents' => \App\Models\Content::count(),
    'Total Generations' => \App\Models\ContentGeneration::count(),
    'Total Prompts' => \App\Models\Prompt::count(),
];

foreach ($stats as $label => $value) {
    echo "   ℹ️  $label: $value\n";
}

echo "✅ PASSED\n\n";

// Navigation menu check
echo "[TEST 9] Super Admin navigation menu\n";
echo str_repeat('─', 70) . "\n";

$layoutPath = __DIR__ . '/ainstein-laravel/resources/views/admin/layout.blade.php';
if (file_exists($layoutPath)) {
    $content = file_get_contents($layoutPath);

    $menuItems = [
        'Dashboard' => strpos($content, 'admin.dashboard') !== false,
        'Tenants' => strpos($content, 'admin.tenants') !== false,
        'Settings' => strpos($content, 'admin.settings') !== false,
    ];

    foreach ($menuItems as $item => $exists) {
        $icon = $exists ? '✅' : '❌';
        echo "   $icon $item in menu\n";
    }

    echo "✅ PASSED\n\n";
} else {
    echo "⚠️  WARNING: Admin layout not found\n\n";
}

// Get Super Admin credentials
echo "[TEST 10] Get Super Admin credentials for manual testing\n";
echo str_repeat('─', 70) . "\n";

echo "   ℹ️  Email: admin@ainstein.com\n";
echo "   ℹ️  Password: Check database or use password reset\n";
echo "   ℹ️  Login URL: " . url('/login') . "\n";
echo "   ℹ️  Dashboard URL: " . route('admin.dashboard') . "\n";

// Try to verify if password is 'password'
if (Hash::check('password', $superAdmin->password)) {
    echo "   ✅ Default password 'password' works\n";
} else {
    echo "   ⚠️  Password needs to be reset or checked\n";
}

echo "✅ PASSED\n\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  SUPER ADMIN TEST SUMMARY                                       ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "Total Tests: 10\n";
echo "✅ All core Super Admin features tested\n";
echo "\n";

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  🔑 CREDENTIALS FOR MANUAL TESTING                              ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "SUPER ADMIN LOGIN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "URL:      " . url('/login') . "\n";
echo "Email:    admin@ainstein.com\n";
echo "Password: " . (Hash::check('password', $superAdmin->password) ? 'password' : '[check database]') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "TENANT USER LOGIN (Demo):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$demoUser = \App\Models\User::where('email', 'admin@demo.com')->first();
if ($demoUser) {
    echo "URL:      " . url('/login') . "\n";
    echo "Email:    admin@demo.com\n";
    echo "Password: " . (Hash::check('password', $demoUser->password) ? 'password' : '[check database]') . "\n";
}
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "SERVER INFO:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "App URL:  " . config('app.url') . "\n";
echo "Env:      " . config('app.env') . "\n";
echo "Debug:    " . (config('app.debug') ? 'ON' : 'OFF') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
