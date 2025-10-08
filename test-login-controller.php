<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "🔐 TEST LOGIN CONTROLLER\n";
echo str_repeat('=', 70) . "\n\n";

// Create fake request
$request = Request::create('/login', 'POST', [
    'email' => 'demo@tenant.com',
    'password' => 'password',
    'remember' => false
]);

$request->headers->set('Accept', 'text/html');

try {
    $controller = new App\Http\Controllers\Auth\AuthController();

    echo "1️⃣  Calling login method...\n";
    $response = $controller->login($request);

    echo "2️⃣  Response received\n";
    echo "   Status: " . $response->getStatusCode() . "\n";

    if ($response->isRedirect()) {
        echo "   Type: REDIRECT\n";
        echo "   Target: " . $response->getTargetUrl() . "\n";

        // Check auth
        if (Auth::check()) {
            echo "\n✅ User is authenticated!\n";
            echo "   User: " . Auth::user()->email . "\n";
            echo "   Name: " . Auth::user()->name . "\n";
        } else {
            echo "\n❌ User NOT authenticated after login\n";
        }
    } else {
        echo "   Type: NOT A REDIRECT\n";
        echo "   Content: " . substr($response->getContent(), 0, 200) . "\n";
    }

    echo "\n✅ LOGIN CONTROLLER TEST PASSED\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo str_repeat('=', 70) . "\n";
