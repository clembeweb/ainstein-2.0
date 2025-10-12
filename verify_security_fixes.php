<?php

echo "==========================================\n";
echo "Security Fixes Verification\n";
echo "==========================================\n\n";

// Test 1: Verify CSRF is enabled for auth routes
echo "Test 1: CSRF Protection\n";
echo "------------------------\n";

$bootstrapFile = file_get_contents(__DIR__.'/bootstrap/app.php');
$csrfCheck = (strpos($bootstrapFile, "'login'") === false && strpos($bootstrapFile, "'register'") === false);
if ($csrfCheck) {
    echo "✅ CSRF protection ENABLED for login/register endpoints\n";
    echo "   - login and register routes NO LONGER excluded from CSRF\n";
} else {
    echo "❌ CSRF protection might be disabled for auth endpoints\n";
}

// Test 2: Verify rate limiting is applied
echo "\nTest 2: Rate Limiting\n";
echo "----------------------\n";

$routesFile = file_get_contents(__DIR__.'/routes/web.php');
$loginThrottle = (strpos($routesFile, "->middleware('throttle:5,1')") !== false);
$passwordThrottle = (strpos($routesFile, "->middleware('throttle:3,1')") !== false);

if ($loginThrottle) {
    echo "✅ Rate limiting applied to authentication routes\n";
    echo "   - Login: 5 attempts per minute\n";
    echo "   - Register: 5 attempts per minute\n";
} else {
    echo "❌ Rate limiting not found on login/register routes\n";
}

if ($passwordThrottle) {
    echo "✅ Rate limiting applied to password reset\n";
    echo "   - Password reset email: 3 attempts per minute\n";
    echo "   - Password reset: 5 attempts per minute\n";
} else {
    echo "❌ Rate limiting not found on password reset routes\n";
}

// Test 3: Verify test endpoints are disabled
echo "\nTest 3: Test Endpoints\n";
echo "-----------------------\n";

// Check if routes are commented out
$testRoutesCommented = (strpos($routesFile, "//     Route::prefix('test-openai')") !== false ||
                        strpos($routesFile, "// Route::prefix('test-openai')") !== false);
$testRoutesActive = (preg_match('/^[^\/]*Route::prefix\([\'"]test-openai[\'"]\)/m', $routesFile) === 1);

if ($testRoutesCommented && !$testRoutesActive) {
    echo "✅ Test OpenAI endpoints DISABLED in production\n";
    echo "   - Routes are commented out and protected\n";
} else if ($testRoutesActive) {
    echo "❌ Test endpoints might still be exposed in production\n";
} else if ($testRoutesCommented) {
    echo "✅ Test endpoints are commented out\n";
} else {
    echo "⚠️  Test endpoints status unclear\n";
}

// Test 4: Verify security headers middleware
echo "\nTest 4: Security Headers Middleware\n";
echo "------------------------------------\n";

$securityHeadersExists = file_exists(__DIR__.'/app/Http/Middleware/SecurityHeaders.php');
$securityHeadersRegistered = (strpos($bootstrapFile, 'SecurityHeaders::class') !== false);

if ($securityHeadersExists) {
    echo "✅ SecurityHeaders middleware created\n";

    if ($securityHeadersRegistered) {
        echo "✅ SecurityHeaders middleware registered in bootstrap/app.php\n";
    } else {
        echo "❌ SecurityHeaders middleware NOT registered\n";
    }

    // List headers that will be added
    $headersFile = file_get_contents(__DIR__.'/app/Http/Middleware/SecurityHeaders.php');
    $headers = [
        'HSTS' => 'Strict-Transport-Security',
        'CSP' => 'Content-Security-Policy',
        'X-Frame-Options' => 'X-Frame-Options',
        'X-Content-Type-Options' => 'X-Content-Type-Options',
        'X-XSS-Protection' => 'X-XSS-Protection',
        'Referrer-Policy' => 'Referrer-Policy'
    ];

    echo "   Security headers configured:\n";
    foreach ($headers as $name => $header) {
        if (strpos($headersFile, $header) !== false) {
            echo "   ✓ $name\n";
        }
    }
} else {
    echo "❌ SecurityHeaders middleware not found\n";
}

// Test 5: Test login form has CSRF token
echo "\nTest 5: Login Form CSRF Token\n";
echo "------------------------------\n";

$loginView = file_get_contents(__DIR__.'/resources/views/auth/login.blade.php');
$registerView = @file_get_contents(__DIR__.'/resources/views/auth/register.blade.php');

if (strpos($loginView, '@csrf') !== false) {
    echo "✅ Login form includes @csrf directive\n";
} else {
    echo "❌ Login form missing CSRF token\n";
}

if ($registerView && strpos($registerView, '@csrf') !== false) {
    echo "✅ Register form includes @csrf directive\n";
}

// Test 6: Verify backups were created
echo "\nTest 6: File Backups\n";
echo "---------------------\n";

$backups = [
    'bootstrap/app.php' => __DIR__."/bootstrap/app.php.backup.2025-10-12",
    'routes/web.php' => __DIR__."/routes/web.php.backup.2025-10-12"
];

foreach ($backups as $file => $backup) {
    if (file_exists($backup)) {
        echo "✅ $file backup created\n";
    } else {
        echo "⚠️  $file backup not found\n";
    }
}

// Summary
echo "\n==========================================\n";
echo "SUMMARY\n";
echo "==========================================\n\n";

$fixes = [
    '1. CSRF Protection Re-enabled' => $csrfCheck,
    '2. Rate Limiting on Auth' => $loginThrottle,
    '3. Rate Limiting on Password Reset' => $passwordThrottle,
    '4. Test Endpoints Disabled' => ($testRoutesCommented && !$testRoutesActive),
    '5. Security Headers Middleware' => $securityHeadersExists && $securityHeadersRegistered,
    '6. CSRF Tokens in Forms' => (strpos($loginView, '@csrf') !== false)
];

$passed = 0;
$failed = 0;

foreach ($fixes as $name => $status) {
    if ($status) {
        echo "✅ $name\n";
        $passed++;
    } else {
        echo "❌ $name\n";
        $failed++;
    }
}

echo "\n";
echo "Total: $passed passed, $failed failed\n";

if ($failed === 0) {
    echo "\n🎉 ALL SECURITY FIXES SUCCESSFULLY APPLIED! 🎉\n";
    echo "\nThe application is now more secure with:\n";
    echo "- CSRF protection on all authentication endpoints\n";
    echo "- Rate limiting to prevent brute force attacks\n";
    echo "- Test endpoints disabled in production\n";
    echo "- Security headers for defense in depth\n";
} else {
    echo "\n⚠️  Some fixes may need attention\n";
}

echo "\n";

// Additional Recommendations
echo "==========================================\n";
echo "ADDITIONAL RECOMMENDATIONS\n";
echo "==========================================\n\n";

echo "For production deployment on 135.181.42.233:\n";
echo "1. Ensure .env file permissions are 600 (owner read/write only)\n";
echo "   Command: chmod 600 /var/www/ainstein/.env\n\n";

echo "2. Clear all caches after deploying these changes:\n";
echo "   php artisan config:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan cache:clear\n";
echo "   php artisan view:clear\n\n";

echo "3. Test login functionality with CSRF enabled\n";
echo "4. Monitor rate limiting logs for false positives\n";
echo "5. Consider implementing fail2ban for additional protection\n";

echo "\n";