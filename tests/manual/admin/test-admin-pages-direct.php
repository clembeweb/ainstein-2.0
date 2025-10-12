<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "🧪 TEST DIRETTO ADMIN PAGES - SIMULAZIONE BROWSER\n";
echo str_repeat('=', 80) . "\n\n";

// Login as super admin
$admin = App\Models\User::where('email', 'superadmin@ainstein.com')->first();
if (!$admin) {
    echo "❌ Super admin not found\n";
    exit(1);
}

Auth::login($admin);
echo "✅ Logged in as: {$admin->email}\n\n";

// Test 1: Check UserResource is registered
echo "📍 TEST 1: Filament Resources Registration\n";
echo str_repeat('-', 80) . "\n";

$resources = [
    'UserResource' => 'App\\Filament\\Admin\\Resources\\UserResource',
    'TenantResource' => 'App\\Filament\\Admin\\Resources\\TenantResource',
];

foreach ($resources as $name => $class) {
    if (class_exists($class)) {
        echo "✅ $name exists\n";

        // Check navigation properties
        try {
            $label = $class::getNavigationLabel();
            $icon = $class::getNavigationIcon();
            $sort = $class::getNavigationSort();
            $url = $class::getUrl();

            echo "   - Label: $label\n";
            echo "   - Icon: $icon\n";
            echo "   - Sort: $sort\n";
            echo "   - URL: $url\n";
        } catch (\Exception $e) {
            echo "   ⚠️  Error getting navigation: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ $name MISSING\n";
    }
    echo "\n";
}

// Test 2: Check Pages
echo "📍 TEST 2: Filament Custom Pages Registration\n";
echo str_repeat('-', 80) . "\n";

$pages = [
    'Settings' => 'App\\Filament\\Admin\\Pages\\Settings',
    'Subscriptions' => 'App\\Filament\\Admin\\Pages\\Subscriptions',
];

foreach ($pages as $name => $class) {
    if (class_exists($class)) {
        echo "✅ $name page exists\n";

        try {
            $label = $class::getNavigationLabel();
            $icon = $class::getNavigationIcon();
            $sort = $class::getNavigationSort();

            echo "   - Label: $label\n";
            echo "   - Icon: $icon\n";
            echo "   - Sort: $sort\n";
        } catch (\Exception $e) {
            echo "   ⚠️  Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ $name MISSING\n";
    }
    echo "\n";
}

// Test 3: Try to access pages via HTTP simulation
echo "📍 TEST 3: HTTP Page Access Simulation\n";
echo str_repeat('-', 80) . "\n";

$urls = [
    '/admin' => 'Dashboard',
    '/admin/users' => 'Users List',
    '/admin/tenants' => 'Tenants List',
    '/admin/subscriptions' => 'Subscriptions',
    '/admin/settings' => 'Settings',
];

foreach ($urls as $url => $description) {
    echo "Testing: $description ($url)\n";

    $ch = curl_init("http://127.0.0.1:8080$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        echo "   ✅ HTTP 200 OK\n";

        // Check if response contains expected content
        if (stripos($response, $description) !== false || stripos($response, 'Filament') !== false) {
            echo "   ✅ Page loaded correctly\n";
        } else {
            echo "   ⚠️  Page loaded but content unclear\n";
        }
    } elseif ($httpCode == 302) {
        echo "   ⚠️  HTTP 302 Redirect (probably to login)\n";
    } else {
        echo "   ❌ HTTP $httpCode\n";
    }
    echo "\n";
}

// Test 4: Check Filament panel configuration
echo "📍 TEST 4: Filament Panel Configuration\n";
echo str_repeat('-', 80) . "\n";

try {
    $panel = Filament\Facades\Filament::getPanel('admin');

    echo "✅ Admin panel found\n";
    echo "   - ID: " . $panel->getId() . "\n";
    echo "   - Path: " . $panel->getPath() . "\n";

    // Get resources
    $resources = $panel->getResources();
    echo "\n   Registered Resources (" . count($resources) . "):\n";
    foreach ($resources as $resource) {
        echo "      - " . class_basename($resource) . "\n";
    }

    // Get pages
    $pages = $panel->getPages();
    echo "\n   Registered Pages (" . count($pages) . "):\n";
    foreach ($pages as $page) {
        echo "      - " . class_basename($page) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error accessing panel: " . $e->getMessage() . "\n";
}

echo "\n";
echo str_repeat('=', 80) . "\n";
echo "🎯 TEST COMPLETED\n";
echo str_repeat('=', 80) . "\n";
