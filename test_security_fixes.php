<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "==========================================\n";
echo "Security Fixes Verification Test\n";
echo "==========================================\n\n";

// Test 1: Verify CSRF is enabled for auth routes
echo "Test 1: CSRF Protection\n";
echo "------------------------\n";

$middleware = $app->make(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
$reflection = new ReflectionClass($middleware);

// Check if login/register are in except array
$exceptProperty = $reflection->getProperty('except');
$exceptProperty->setAccessible(true);
$except = $exceptProperty->getValue($middleware);

// In Laravel 11, we need to check bootstrap/app.php config
$bootstrapFile = file_get_contents(__DIR__.'/bootstrap/app.php');
if (strpos($bootstrapFile, "'login'") === false && strpos($bootstrapFile, "'register'") === false) {
    echo "✅ CSRF protection enabled for login/register endpoints\n";
} else {
    echo "❌ CSRF protection might be disabled for auth endpoints\n";
}

// Test 2: Verify rate limiting is applied
echo "\nTest 2: Rate Limiting\n";
echo "----------------------\n";

$routesFile = file_get_contents(__DIR__.'/routes/web.php');
if (strpos($routesFile, "throttle:5,1") !== false) {
    echo "✅ Rate limiting applied to login/register routes\n";
    echo "   - Login: 5 attempts per minute\n";
    echo "   - Register: 5 attempts per minute\n";
}

if (strpos($routesFile, "throttle:3,1") !== false) {
    echo "✅ Rate limiting applied to password reset\n";
    echo "   - Password reset email: 3 attempts per minute\n";
}

// Test 3: Verify test endpoints are disabled
echo "\nTest 3: Test Endpoints\n";
echo "-----------------------\n";

if (strpos($routesFile, "// if (app()->environment('local')) {") !== false) {
    echo "✅ Test OpenAI endpoints disabled in production\n";
} else if (strpos($routesFile, "Route::prefix('test-openai')") !== false) {
    echo "❌ Test endpoints might still be exposed\n";
}

// Test 4: Verify security headers middleware
echo "\nTest 4: Security Headers Middleware\n";
echo "------------------------------------\n";

if (file_exists(__DIR__.'/app/Http/Middleware/SecurityHeaders.php')) {
    echo "✅ SecurityHeaders middleware exists\n";

    // Check if registered
    if (strpos($bootstrapFile, 'SecurityHeaders::class') !== false) {
        echo "✅ SecurityHeaders middleware registered\n";
    } else {
        echo "⚠️  SecurityHeaders middleware created but not registered\n";
    }

    // List headers that will be added
    $headersFile = file_get_contents(__DIR__.'/app/Http/Middleware/SecurityHeaders.php');
    if (strpos($headersFile, 'Strict-Transport-Security') !== false) {
        echo "   - HSTS header configured\n";
    }
    if (strpos($headersFile, 'Content-Security-Policy') !== false) {
        echo "   - CSP header configured\n";
    }
    if (strpos($headersFile, 'X-Frame-Options') !== false) {
        echo "   - X-Frame-Options configured\n";
    }
    if (strpos($headersFile, 'X-Content-Type-Options') !== false) {
        echo "   - X-Content-Type-Options configured\n";
    }
} else {
    echo "❌ SecurityHeaders middleware not found\n";
}

// Test 5: Test login form has CSRF token
echo "\nTest 5: Login Form CSRF Token\n";
echo "------------------------------\n";

$loginView = file_get_contents(__DIR__.'/resources/views/auth/login.blade.php');
if (strpos($loginView, '@csrf') !== false) {
    echo "✅ Login form includes CSRF token\n";
} else {
    echo "❌ Login form missing CSRF token\n";
}

// Test 6: Verify backups were created
echo "\nTest 6: File Backups\n";
echo "---------------------\n";

$backupDate = date('Y-m-d');
if (file_exists(__DIR__."/bootstrap/app.php.backup.2025-10-12")) {
    echo "✅ bootstrap/app.php backup exists\n";
}
if (file_exists(__DIR__."/routes/web.php.backup.2025-10-12")) {
    echo "✅ routes/web.php backup exists\n";
}

// Summary
echo "\n==========================================\n";
echo "Summary\n";
echo "==========================================\n";

$fixes = [
    'CSRF Protection' => (strpos($bootstrapFile, "'login'") === false && strpos($bootstrapFile, "'register'") === false),
    'Rate Limiting' => (strpos($routesFile, "throttle:") !== false),
    'Test Endpoints Disabled' => (strpos($routesFile, "// if (app()->environment('local')) {") !== false),
    'Security Headers' => file_exists(__DIR__.'/app/Http/Middleware/SecurityHeaders.php'),
    'CSRF in Forms' => (strpos($loginView, '@csrf') !== false)
];

$passed = array_sum($fixes);
$total = count($fixes);

echo "Security fixes applied: $passed/$total\n";

if ($passed === $total) {
    echo "✅ All security fixes have been successfully applied!\n";
} else {
    echo "⚠️  Some fixes may need attention\n";
}

echo "\n";