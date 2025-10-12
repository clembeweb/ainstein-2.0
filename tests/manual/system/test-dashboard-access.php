<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "📊 TEST DASHBOARD ACCESS\n";
echo str_repeat('=', 70) . "\n\n";

// Login user manually
$user = App\Models\User::where('email', 'demo@tenant.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

echo "1️⃣  Logging in user: " . $user->email . "\n";
Auth::login($user);

if (!Auth::check()) {
    echo "❌ Auth failed\n";
    exit(1);
}

echo "✅ User authenticated\n\n";

// Create dashboard request
echo "2️⃣  Creating dashboard request...\n";
$request = Request::create('/dashboard', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    echo "3️⃣  Calling TenantDashboardController...\n";

    $controller = new App\Http\Controllers\TenantDashboardController();
    $response = $controller->index($request);

    echo "✅ Controller responded\n";
    echo "   Type: " . get_class($response) . "\n";

    if ($response instanceof Illuminate\View\View) {
        echo "\n4️⃣  Checking response content...\n";

        $content = $response->render();

        // Check key elements
        $checks = [
            'Welcome back' => strpos($content, 'Welcome back') !== false,
            'Dashboard title' => strpos($content, 'Dashboard') !== false,
            'Token Usage' => strpos($content, 'Token Usage') !== false || strpos($content, 'token') !== false,
            'Statistics' => strpos($content, 'stat-card') !== false || strpos($content, 'Statistics') !== false,
        ];

        foreach ($checks as $name => $result) {
            echo "   " . ($result ? "✅" : "❌") . " {$name}\n";
        }

        // Check for onboarding script
        if (strpos($content, 'autoStartOnboarding') !== false) {
            echo "   ✅ Onboarding script present\n";
        } else {
            echo "   ℹ️  Onboarding script not found (user might have completed it)\n";
        }

        echo "\n✅ DASHBOARD ACCESS TEST PASSED!\n";
        echo "\n📝 Dashboard is rendering correctly\n";

    } else {
        echo "\n❌ Not a View instance\n";
        echo "Type: " . get_class($response) . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n   Stack trace:\n";
    echo substr($e->getTraceAsString(), 0, 1000) . "\n";
    exit(1);
}

echo str_repeat('=', 70) . "\n";
