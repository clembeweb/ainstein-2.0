<?php

/**
 * Production Login Test Script
 *
 * Run this script to test login functionality on production
 * Usage: php test_production_login.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

class ProductionLoginTester
{
    private $baseUrl = 'https://ainstein.it';
    private $results = [];
    private $cookieJar = [];

    public function run()
    {
        echo "\n🔍 AINSTEIN PRODUCTION LOGIN TEST\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

        // Run all tests
        $this->testLoginPageAccessibility();
        $this->testCsrfTokenRetrieval();
        $this->testLoginWithValidCredentials();
        $this->testLoginWithInvalidCredentials();
        $this->testSessionPersistence();
        $this->testLogoutFunctionality();
        $this->testHttpsRedirect();
        $this->testRateLimiting();

        // Generate report
        $this->generateReport();
    }

    /**
     * Test 1: Login page accessibility
     */
    private function testLoginPageAccessibility()
    {
        echo "📋 Test 1: Login Page Accessibility\n";

        try {
            $ch = curl_init($this->baseUrl . '/login');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADER => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            $this->results['login_page_accessibility'] = [
                'status' => $httpCode === 200 ? 'PASS' : 'FAIL',
                'http_code' => $httpCode,
                'final_url' => $finalUrl,
                'uses_https' => str_starts_with($finalUrl, 'https://'),
                'page_loads' => !empty($response)
            ];

            echo "   Status Code: $httpCode\n";
            echo "   Final URL: $finalUrl\n";
            echo "   Result: " . ($httpCode === 200 ? '✅ PASS' : '❌ FAIL') . "\n\n";

        } catch (Exception $e) {
            $this->results['login_page_accessibility'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Test 2: CSRF Token Retrieval
     */
    private function testCsrfTokenRetrieval()
    {
        echo "🔐 Test 2: CSRF Token Retrieval\n";

        try {
            $ch = curl_init($this->baseUrl . '/login');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADER => true
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            // Extract CSRF token
            $csrfToken = null;
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $matches)) {
                $csrfToken = $matches[1];
            } elseif (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $response, $matches)) {
                $csrfToken = $matches[1];
            }

            // Extract session cookie
            if (preg_match('/Set-Cookie: laravel_session=([^;]+)/i', $response, $matches)) {
                $this->cookieJar['laravel_session'] = $matches[1];
            }

            $this->results['csrf_token_retrieval'] = [
                'status' => $csrfToken ? 'PASS' : 'FAIL',
                'token_found' => !empty($csrfToken),
                'token_length' => strlen($csrfToken ?? ''),
                'session_cookie_set' => !empty($this->cookieJar['laravel_session'])
            ];

            // Store for later tests
            $this->results['_csrf_token'] = $csrfToken;

            echo "   Token Found: " . (!empty($csrfToken) ? 'Yes' : 'No') . "\n";
            echo "   Token Length: " . strlen($csrfToken ?? '') . "\n";
            echo "   Session Cookie: " . (!empty($this->cookieJar['laravel_session']) ? 'Set' : 'Not Set') . "\n";
            echo "   Result: " . ($csrfToken ? '✅ PASS' : '❌ FAIL') . "\n\n";

        } catch (Exception $e) {
            $this->results['csrf_token_retrieval'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Test 3: Login with Valid Credentials
     */
    private function testLoginWithValidCredentials()
    {
        echo "✅ Test 3: Login with Valid Credentials\n";

        $csrfToken = $this->results['_csrf_token'] ?? '';

        if (empty($csrfToken)) {
            echo "   ⚠️ SKIPPED: No CSRF token available\n\n";
            $this->results['login_valid_credentials'] = [
                'status' => 'SKIPPED',
                'reason' => 'No CSRF token'
            ];
            return;
        }

        try {
            // Note: These are test credentials - replace with actual test account
            $postData = http_build_query([
                '_token' => $csrfToken,
                'email' => 'admin@ainstein.it',
                'password' => 'Admin@2025!' // Test password
            ]);

            $ch = curl_init($this->baseUrl . '/login');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects automatically
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN: ' . $csrfToken,
                    'Accept: text/html,application/xhtml+xml',
                    'Origin: ' . $this->baseUrl,
                    'Referer: ' . $this->baseUrl . '/login'
                ],
                CURLOPT_COOKIE => $this->buildCookieString()
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Check for redirect (302) which indicates successful login
            $isRedirect = in_array($httpCode, [301, 302, 303, 307, 308]);
            $location = '';

            if (preg_match('/Location: ([^\r\n]+)/i', $response, $matches)) {
                $location = trim($matches[1]);
            }

            // Update session cookie if set
            if (preg_match('/Set-Cookie: laravel_session=([^;]+)/i', $response, $matches)) {
                $this->cookieJar['laravel_session'] = $matches[1];
            }

            $expectedRedirect = '/admin'; // For super admin
            $loginSuccessful = $isRedirect && str_contains($location, $expectedRedirect);

            $this->results['login_valid_credentials'] = [
                'status' => $loginSuccessful ? 'PASS' : 'FAIL',
                'http_code' => $httpCode,
                'is_redirect' => $isRedirect,
                'redirect_location' => $location,
                'expected_redirect' => $expectedRedirect,
                'matches_expected' => str_contains($location, $expectedRedirect),
                'session_updated' => !empty($this->cookieJar['laravel_session'])
            ];

            echo "   HTTP Code: $httpCode\n";
            echo "   Is Redirect: " . ($isRedirect ? 'Yes' : 'No') . "\n";
            echo "   Location: $location\n";
            echo "   Expected: $expectedRedirect\n";
            echo "   Result: " . ($loginSuccessful ? '✅ PASS' : '❌ FAIL') . "\n\n";

        } catch (Exception $e) {
            $this->results['login_valid_credentials'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Test 4: Login with Invalid Credentials
     */
    private function testLoginWithInvalidCredentials()
    {
        echo "❌ Test 4: Login with Invalid Credentials\n";

        $csrfToken = $this->results['_csrf_token'] ?? '';

        if (empty($csrfToken)) {
            echo "   ⚠️ SKIPPED: No CSRF token available\n\n";
            $this->results['login_invalid_credentials'] = [
                'status' => 'SKIPPED',
                'reason' => 'No CSRF token'
            ];
            return;
        }

        try {
            $postData = http_build_query([
                '_token' => $csrfToken,
                'email' => 'admin@ainstein.it',
                'password' => 'WrongPassword123!'
            ]);

            $ch = curl_init($this->baseUrl . '/login');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN: ' . $csrfToken,
                    'Accept: text/html,application/xhtml+xml',
                    'Origin: ' . $this->baseUrl,
                    'Referer: ' . $this->baseUrl . '/login'
                ],
                CURLOPT_COOKIE => $this->buildCookieString()
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Should redirect back to login with errors (302) or show login page (200)
            $isRedirect = in_array($httpCode, [302, 303]);
            $location = '';

            if (preg_match('/Location: ([^\r\n]+)/i', $response, $matches)) {
                $location = trim($matches[1]);
            }

            // Check if redirected back to login (indicates failure)
            $redirectsToLogin = str_contains($location, '/login');
            $loginFailed = ($isRedirect && $redirectsToLogin) || $httpCode === 200;

            $this->results['login_invalid_credentials'] = [
                'status' => $loginFailed ? 'PASS' : 'FAIL', // PASS means it correctly rejected invalid credentials
                'http_code' => $httpCode,
                'is_redirect' => $isRedirect,
                'redirect_location' => $location,
                'redirects_to_login' => $redirectsToLogin,
                'login_rejected' => $loginFailed
            ];

            echo "   HTTP Code: $httpCode\n";
            echo "   Login Rejected: " . ($loginFailed ? 'Yes' : 'No') . "\n";
            echo "   Result: " . ($loginFailed ? '✅ PASS (Invalid credentials rejected)' : '❌ FAIL') . "\n\n";

        } catch (Exception $e) {
            $this->results['login_invalid_credentials'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Test 5: Session Persistence
     */
    private function testSessionPersistence()
    {
        echo "🔄 Test 5: Session Persistence\n";

        if (empty($this->cookieJar['laravel_session'])) {
            echo "   ⚠️ SKIPPED: No session cookie available\n\n";
            $this->results['session_persistence'] = [
                'status' => 'SKIPPED',
                'reason' => 'No session cookie'
            ];
            return;
        }

        try {
            // Try to access protected page with session
            $ch = curl_init($this->baseUrl . '/admin');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADER => true,
                CURLOPT_COOKIE => $this->buildCookieString()
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 200 means we can access the page (session valid)
            // 302 means redirect to login (session invalid)
            $sessionValid = $httpCode === 200;

            $this->results['session_persistence'] = [
                'status' => $sessionValid ? 'PASS' : 'FAIL',
                'http_code' => $httpCode,
                'session_valid' => $sessionValid,
                'can_access_protected_page' => $sessionValid
            ];

            echo "   HTTP Code: $httpCode\n";
            echo "   Session Valid: " . ($sessionValid ? 'Yes' : 'No') . "\n";
            echo "   Result: " . ($sessionValid ? '✅ PASS' : '❌ FAIL') . "\n\n";

        } catch (Exception $e) {
            $this->results['session_persistence'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Test 6: Logout Functionality
     */
    private function testLogoutFunctionality()
    {
        echo "🚪 Test 6: Logout Functionality\n";

        $csrfToken = $this->results['_csrf_token'] ?? '';

        if (empty($csrfToken) || empty($this->cookieJar['laravel_session'])) {
            echo "   ⚠️ SKIPPED: No session or CSRF token available\n\n";
            $this->results['logout_functionality'] = [
                'status' => 'SKIPPED',
                'reason' => 'No session or CSRF token'
            ];
            return;
        }

        try {
            $postData = http_build_query([
                '_token' => $csrfToken
            ]);

            $ch = curl_init($this->baseUrl . '/logout');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN: ' . $csrfToken
                ],
                CURLOPT_COOKIE => $this->buildCookieString()
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $isRedirect = in_array($httpCode, [301, 302, 303, 307, 308]);
            $location = '';

            if (preg_match('/Location: ([^\r\n]+)/i', $response, $matches)) {
                $location = trim($matches[1]);
            }

            $logoutSuccessful = $isRedirect && ($location === '/' || str_ends_with($location, $this->baseUrl . '/'));

            $this->results['logout_functionality'] = [
                'status' => $logoutSuccessful ? 'PASS' : 'FAIL',
                'http_code' => $httpCode,
                'is_redirect' => $isRedirect,
                'redirect_location' => $location,
                'redirects_to_home' => $logoutSuccessful
            ];

            echo "   HTTP Code: $httpCode\n";
            echo "   Redirects to Home: " . ($logoutSuccessful ? 'Yes' : 'No') . "\n";
            echo "   Result: " . ($logoutSuccessful ? '✅ PASS' : '❌ FAIL') . "\n\n";

        } catch (Exception $e) {
            $this->results['logout_functionality'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Test 7: HTTPS Redirect
     */
    private function testHttpsRedirect()
    {
        echo "🔒 Test 7: HTTPS Redirect\n";

        try {
            // Test HTTP URL
            $ch = curl_init('http://ainstein.it/login');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADER => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $isRedirect = in_array($httpCode, [301, 302, 307, 308]);
            $location = '';

            if (preg_match('/Location: ([^\r\n]+)/i', $response, $matches)) {
                $location = trim($matches[1]);
            }

            $redirectsToHttps = str_starts_with($location, 'https://');

            $this->results['https_redirect'] = [
                'status' => $redirectsToHttps ? 'PASS' : 'FAIL',
                'http_code' => $httpCode,
                'is_redirect' => $isRedirect,
                'redirect_location' => $location,
                'redirects_to_https' => $redirectsToHttps
            ];

            echo "   HTTP Code: $httpCode\n";
            echo "   Redirects to HTTPS: " . ($redirectsToHttps ? 'Yes' : 'No') . "\n";
            echo "   Result: " . ($redirectsToHttps ? '✅ PASS' : '❌ FAIL') . "\n\n";

        } catch (Exception $e) {
            $this->results['https_redirect'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Test 8: Rate Limiting
     */
    private function testRateLimiting()
    {
        echo "🛡️ Test 8: Rate Limiting\n";

        $csrfToken = $this->results['_csrf_token'] ?? '';

        if (empty($csrfToken)) {
            echo "   ⚠️ SKIPPED: No CSRF token available\n\n";
            $this->results['rate_limiting'] = [
                'status' => 'SKIPPED',
                'reason' => 'No CSRF token'
            ];
            return;
        }

        try {
            $attempts = [];

            // Make 6 rapid failed login attempts
            for ($i = 1; $i <= 6; $i++) {
                $postData = http_build_query([
                    '_token' => $csrfToken,
                    'email' => 'admin@ainstein.it',
                    'password' => 'WrongPassword' . $i
                ]);

                $ch = curl_init($this->baseUrl . '/login');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData,
                    CURLOPT_HEADER => true,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN: ' . $csrfToken
                    ],
                    CURLOPT_COOKIE => $this->buildCookieString()
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $attempts[] = [
                    'attempt' => $i,
                    'http_code' => $httpCode,
                    'is_429' => $httpCode === 429
                ];

                // Short delay between attempts
                usleep(100000); // 100ms
            }

            // Check if any attempt was rate limited
            $rateLimitDetected = array_filter($attempts, fn($a) => $a['is_429']);

            $this->results['rate_limiting'] = [
                'status' => !empty($rateLimitDetected) ? 'PASS' : 'INFO',
                'attempts' => count($attempts),
                'rate_limit_detected' => !empty($rateLimitDetected),
                'first_rate_limit_at' => !empty($rateLimitDetected) ? min(array_column($rateLimitDetected, 'attempt')) : null,
                'note' => empty($rateLimitDetected) ? 'Rate limiting might not be enabled' : 'Rate limiting is active'
            ];

            echo "   Attempts Made: " . count($attempts) . "\n";
            echo "   Rate Limit Detected: " . (!empty($rateLimitDetected) ? 'Yes' : 'No') . "\n";
            echo "   Result: " . (!empty($rateLimitDetected) ? '✅ PASS' : 'ℹ️ INFO - Rate limiting might not be enabled') . "\n\n";

        } catch (Exception $e) {
            $this->results['rate_limiting'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            echo "   ⚠️ ERROR: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Build cookie string from cookie jar
     */
    private function buildCookieString()
    {
        $cookies = [];
        foreach ($this->cookieJar as $name => $value) {
            $cookies[] = "$name=$value";
        }
        return implode('; ', $cookies);
    }

    /**
     * Generate final report
     */
    private function generateReport()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 FINAL REPORT\n";
        echo str_repeat("=", 60) . "\n\n";

        $summary = [
            'PASS' => 0,
            'FAIL' => 0,
            'ERROR' => 0,
            'SKIPPED' => 0,
            'INFO' => 0
        ];

        foreach ($this->results as $key => $result) {
            if (str_starts_with($key, '_')) continue; // Skip internal keys

            $status = $result['status'] ?? 'UNKNOWN';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        echo "Test Results Summary:\n";
        echo "✅ Passed: {$summary['PASS']}\n";
        echo "❌ Failed: {$summary['FAIL']}\n";
        echo "⚠️ Errors: {$summary['ERROR']}\n";
        echo "⏭️ Skipped: {$summary['SKIPPED']}\n";
        echo "ℹ️ Info: {$summary['INFO']}\n";
        echo "\nTotal Tests: " . array_sum($summary) . "\n\n";

        // Overall status
        if ($summary['FAIL'] > 0 || $summary['ERROR'] > 0) {
            echo "🔴 OVERALL STATUS: FAILURE\n";
            echo "There are issues with the login system that need attention.\n\n";

            // List failed tests
            echo "Failed/Error Tests:\n";
            foreach ($this->results as $key => $result) {
                if (str_starts_with($key, '_')) continue;
                if (in_array($result['status'] ?? '', ['FAIL', 'ERROR'])) {
                    echo "  - " . str_replace('_', ' ', ucwords($key)) . ": {$result['status']}\n";
                    if (isset($result['error'])) {
                        echo "    Error: {$result['error']}\n";
                    }
                }
            }
        } else {
            echo "🟢 OVERALL STATUS: SUCCESS\n";
            echo "Login system is functioning correctly!\n";
        }

        // Save detailed report
        $reportFile = __DIR__ . '/storage/logs/production_login_test_' . date('Y-m-d_H-i-s') . '.json';
        if (!is_dir(dirname($reportFile))) {
            mkdir(dirname($reportFile), 0755, true);
        }

        file_put_contents($reportFile, json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'base_url' => $this->baseUrl,
            'summary' => $summary,
            'results' => $this->results
        ], JSON_PRETTY_PRINT));

        echo "\n📁 Detailed report saved to:\n$reportFile\n";
    }
}

// Run the tests
$tester = new ProductionLoginTester();
$tester->run();