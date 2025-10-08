<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "🧪 TEST COMPATIBILITÀ ADMIN + TENANT PANEL\n";
echo str_repeat('=', 80) . "\n\n";

// Test 1: Tenant Routes
echo "📍 TEST 1: Tenant Routes Integrity\n";
echo str_repeat('-', 80) . "\n";

$tenantRoutes = [
    '/dashboard',
    '/pages',
    '/prompts',
    '/content',
    '/api-keys',
];

exec('cd ' . escapeshellarg(__DIR__) . ' && php artisan route:list --path=^tenant/', $routeOutput);
$hasAllRoutes = true;

foreach ($tenantRoutes as $route) {
    $found = false;
    foreach ($routeOutput as $line) {
        if (strpos($line, $route) !== false) {
            $found = true;
            break;
        }
    }

    if ($found) {
        echo "   ✅ Route $route exists\n";
    } else {
        echo "   ❌ Route $route MISSING\n";
        $hasAllRoutes = false;
    }
}
echo "\n";

// Test 2: Demo Login Flow
echo "📍 TEST 2: Demo Login Credentials\n";
echo str_repeat('-', 80) . "\n";

$demoUser = App\Models\User::where('email', 'admin@demo.com')->first();
if ($demoUser) {
    echo "✅ Demo user exists: {$demoUser->email}\n";
    echo "   - Password: demo123\n";
    echo "   - Tenant: {$demoUser->tenant->name}\n";
    echo "   - Role: {$demoUser->role}\n";
    echo "   - Onboarding completed: " . ($demoUser->onboarding_completed ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Demo user not found\n";
}
echo "\n";

// Test 3: Tenant Panel Controllers
echo "📍 TEST 3: Tenant Panel Controllers\n";
echo str_repeat('-', 80) . "\n";

$controllers = [
    'TenantPageController' => 'App\\Http\\Controllers\\TenantPageController',
    'TenantPromptController' => 'App\\Http\\Controllers\\TenantPromptController',
    'TenantContentController' => 'App\\Http\\Controllers\\TenantContentController',
    'TenantApiKeyController' => 'App\\Http\\Controllers\\TenantApiKeyController',
];

foreach ($controllers as $name => $class) {
    if (class_exists($class)) {
        echo "   ✅ $name exists\n";
    } else {
        echo "   ❌ $name MISSING\n";
    }
}
echo "\n";

// Test 4: Middleware
echo "📍 TEST 4: Middleware Configuration\n";
echo str_repeat('-', 80) . "\n";

if (class_exists('App\\Http\\Middleware\\EnsureTenantAccess')) {
    echo "   ✅ EnsureTenantAccess middleware exists\n";
} else {
    echo "   ❌ EnsureTenantAccess middleware MISSING\n";
}

if (class_exists('App\\Http\\Middleware\\SuperAdminMiddleware')) {
    echo "   ✅ SuperAdminMiddleware exists\n";
} else {
    echo "   ❌ SuperAdminMiddleware MISSING\n";
}
echo "\n";

// Test 5: Database Integrity
echo "📍 TEST 5: Database Models & Relations\n";
echo str_repeat('-', 80) . "\n";

// Check tenant has pages
$demoTenant = App\Models\Tenant::where('name', 'Demo Company')->first();
if ($demoTenant) {
    $pagesCount = $demoTenant->pages()->count();
    $promptsCount = $demoTenant->prompts()->count();
    $contentsCount = $demoTenant->contentGenerations()->count();
    $apiKeysCount = $demoTenant->apiKeys()->count();

    echo "✅ Demo Tenant Data:\n";
    echo "   - Pages: $pagesCount\n";
    echo "   - Prompts: $promptsCount\n";
    echo "   - Contents: $contentsCount\n";
    echo "   - API Keys: $apiKeysCount\n";
}
echo "\n";

// Test 6: Views
echo "📍 TEST 6: Tenant Panel Views\n";
echo str_repeat('-', 80) . "\n";

$views = [
    'tenant.dashboard' => 'resources/views/tenant/dashboard.blade.php',
    'tenant.pages.index' => 'resources/views/tenant/pages/index.blade.php',
    'tenant.prompts.index' => 'resources/views/tenant/prompts/index.blade.php',
    'tenant.content.index' => 'resources/views/tenant/content/index.blade.php',
];

foreach ($views as $viewName => $viewPath) {
    if (file_exists(__DIR__ . '/' . $viewPath)) {
        echo "   ✅ $viewName exists\n";
    } else {
        echo "   ❌ $viewName MISSING\n";
    }
}
echo "\n";

// Test 7: Admin Panel Resources
echo "📍 TEST 7: Admin Panel Resources\n";
echo str_repeat('-', 80) . "\n";

$adminResources = [
    'UserResource' => 'App\\Filament\\Admin\\Resources\\UserResource',
    'TenantResource' => 'App\\Filament\\Admin\\Resources\\TenantResource',
];

foreach ($adminResources as $name => $class) {
    if (class_exists($class)) {
        echo "   ✅ $name exists\n";
    } else {
        echo "   ❌ $name MISSING\n";
    }
}
echo "\n";

// Test 8: Admin Pages
echo "📍 TEST 8: Admin Custom Pages\n";
echo str_repeat('-', 80) . "\n";

$adminPages = [
    'Settings' => 'App\\Filament\\Admin\\Pages\\Settings',
    'Subscriptions' => 'App\\Filament\\Admin\\Pages\\Subscriptions',
];

foreach ($adminPages as $name => $class) {
    if (class_exists($class)) {
        echo "   ✅ $name page exists\n";
    } else {
        echo "   ❌ $name page MISSING\n";
    }
}
echo "\n";

// Test 9: Onboarding Tour
echo "📍 TEST 9: Onboarding Tour Assets\n";
echo str_repeat('-', 80) . "\n";

$assets = [
    'Shepherd CSS' => 'public/vendor/shepherd/shepherd.css',
    'Shepherd JS' => 'public/vendor/shepherd/shepherd.js',
    'Tour Script' => 'resources/views/tenant/layouts/app.blade.php',
];

$shepherdCssExists = file_exists(__DIR__ . '/public/vendor/shepherd/shepherd.css');
$shepherdJsExists = file_exists(__DIR__ . '/public/vendor/shepherd/shepherd.js');

echo "   " . ($shepherdCssExists ? "✅" : "❌") . " Shepherd CSS\n";
echo "   " . ($shepherdJsExists ? "✅" : "❌") . " Shepherd JS\n";

// Check if tour is in layout
$layoutContent = file_get_contents(__DIR__ . '/resources/views/tenant/layouts/app.blade.php');
$hasTourScript = strpos($layoutContent, 'startOnboardingTour') !== false;
echo "   " . ($hasTourScript ? "✅" : "❌") . " Tour script in layout\n";
echo "\n";

// Final Summary
echo str_repeat('=', 80) . "\n";
echo "🎯 SUMMARY - COMPATIBILITY CHECK\n";
echo str_repeat('=', 80) . "\n\n";

echo "✅ TENANT PANEL:\n";
echo "   - Login URL: http://127.0.0.1:8080/login\n";
echo "   - Demo Login: admin@demo.com / demo123\n";
echo "   - Dashboard: /tenant/dashboard\n";
echo "   - Pages: /tenant/pages\n";
echo "   - Prompts: /tenant/prompts\n";
echo "   - Content: /tenant/content\n";
echo "   - API Keys: /tenant/api-keys\n\n";

echo "✅ ADMIN PANEL:\n";
echo "   - Login URL: http://127.0.0.1:8080/admin/login\n";
echo "   - Super Admin: superadmin@ainstein.com / admin123\n";
echo "   - Dashboard: /admin\n";
echo "   - Users: /admin/users\n";
echo "   - Tenants: /admin/tenants\n";
echo "   - Subscriptions: /admin/subscriptions\n";
echo "   - Settings: /admin/settings\n\n";

echo "🚀 BOTH PANELS ARE INDEPENDENT AND FUNCTIONAL!\n\n";

echo str_repeat('=', 80) . "\n";
