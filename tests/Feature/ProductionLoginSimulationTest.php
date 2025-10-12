<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Production Login Simulation Test
 *
 * This test suite simulates real production login scenarios
 * using HTTP client to mimic browser behavior with cookies and sessions
 */
class ProductionLoginSimulationTest extends TestCase
{
    protected $baseUrl = 'https://ainstein.it';
    protected $testCredentials = [
        'super_admin' => [
            'email' => 'admin@ainstein.it',
            'password' => 'Admin@2025!',
            'expected_redirect' => '/admin'
        ],
        'tenant_user' => [
            'email' => 'user@test-company.com',
            'password' => 'User@2025!',
            'expected_redirect' => '/dashboard'
        ]
    ];

    /**
     * Test complete login flow simulation
     */
    public function test_complete_login_flow_simulation()
    {
        $this->markTestSkipped('This test requires production environment access');

        $results = [
            'timestamp' => now()->toIso8601String(),
            'environment' => 'production',
            'base_url' => $this->baseUrl,
            'tests' => []
        ];

        // Test 1: Get CSRF Token
        Log::info('🔍 TEST 1: Fetching CSRF Token');
        $csrfToken = $this->fetchCsrfToken();
        $results['tests']['csrf_token'] = [
            'status' => $csrfToken ? 'PASS' : 'FAIL',
            'token_obtained' => !empty($csrfToken)
        ];

        // Test 2: Login with super admin
        Log::info('🔐 TEST 2: Super Admin Login');
        $adminLogin = $this->testLoginFlow(
            $this->testCredentials['super_admin'],
            $csrfToken
        );
        $results['tests']['super_admin_login'] = $adminLogin;

        // Test 3: Login with tenant user
        Log::info('👤 TEST 3: Tenant User Login');
        $userLogin = $this->testLoginFlow(
            $this->testCredentials['tenant_user'],
            $csrfToken
        );
        $results['tests']['tenant_user_login'] = $userLogin;

        // Test 4: Invalid credentials
        Log::info('❌ TEST 4: Invalid Credentials');
        $invalidLogin = $this->testLoginFlow([
            'email' => 'admin@ainstein.it',
            'password' => 'WrongPassword123!',
            'expected_redirect' => null
        ], $csrfToken);
        $results['tests']['invalid_credentials'] = $invalidLogin;

        // Test 5: Session persistence
        Log::info('🔄 TEST 5: Session Persistence');
        $sessionTest = $this->testSessionPersistence($csrfToken);
        $results['tests']['session_persistence'] = $sessionTest;

        // Test 6: Logout flow
        Log::info('🚪 TEST 6: Logout Flow');
        $logoutTest = $this->testLogoutFlow($csrfToken);
        $results['tests']['logout'] = $logoutTest;

        // Test 7: HTTPS enforcement
        Log::info('🔒 TEST 7: HTTPS Enforcement');
        $httpsTest = $this->testHttpsEnforcement();
        $results['tests']['https_enforcement'] = $httpsTest;

        // Generate report
        $this->generateReport($results);
    }

    /**
     * Fetch CSRF token from login page
     */
    protected function fetchCsrfToken()
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'allow_redirects' => true
            ])->get($this->baseUrl . '/login');

            if ($response->successful()) {
                // Parse CSRF token from HTML
                preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response->body(), $matches);

                if (isset($matches[1])) {
                    return $matches[1];
                }

                // Alternative: Look for _token in form
                preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $response->body(), $matches);

                return $matches[1] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch CSRF token: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Test login flow with given credentials
     */
    protected function testLoginFlow(array $credentials, string $csrfToken)
    {
        $result = [
            'email' => $credentials['email'],
            'timestamp' => now()->toIso8601String(),
            'steps' => []
        ];

        try {
            // Step 1: Submit login form
            $cookies = [];
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'allow_redirects' => false
            ])
            ->withHeaders([
                'X-CSRF-TOKEN' => $csrfToken,
                'Accept' => 'text/html,application/xhtml+xml',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Origin' => $this->baseUrl,
                'Referer' => $this->baseUrl . '/login'
            ])
            ->asForm()
            ->post($this->baseUrl . '/login', [
                '_token' => $csrfToken,
                'email' => $credentials['email'],
                'password' => $credentials['password']
            ]);

            $result['steps']['login_post'] = [
                'status_code' => $response->status(),
                'is_redirect' => $response->redirect(),
                'location' => $response->header('Location')
            ];

            // Step 2: Check if login was successful
            if ($response->redirect()) {
                $location = $response->header('Location');

                if ($credentials['expected_redirect']) {
                    $expectedUrl = $this->baseUrl . $credentials['expected_redirect'];
                    $result['login_successful'] = str_contains($location, $credentials['expected_redirect']);
                } else {
                    // Expected to fail
                    $result['login_successful'] = false;
                }

                // Extract session cookie
                $setCookies = $response->header('Set-Cookie');
                if ($setCookies) {
                    preg_match('/laravel_session=([^;]+)/', $setCookies, $matches);
                    $result['session_cookie'] = isset($matches[1]) ? 'obtained' : 'missing';
                }
            } else {
                $result['login_successful'] = false;
                $result['error'] = 'No redirect received';
            }

            $result['status'] = $result['login_successful'] ? 'PASS' : 'FAIL';

        } catch (\Exception $e) {
            $result['status'] = 'ERROR';
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Test session persistence after login
     */
    protected function testSessionPersistence(string $csrfToken)
    {
        $result = [
            'timestamp' => now()->toIso8601String(),
            'tests' => []
        ];

        try {
            // Login first
            $loginResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'allow_redirects' => false
            ])
            ->asForm()
            ->post($this->baseUrl . '/login', [
                '_token' => $csrfToken,
                'email' => $this->testCredentials['super_admin']['email'],
                'password' => $this->testCredentials['super_admin']['password']
            ]);

            // Extract session cookie
            $sessionCookie = null;
            $setCookies = $loginResponse->header('Set-Cookie');
            if ($setCookies) {
                preg_match('/laravel_session=([^;]+)/', $setCookies, $matches);
                $sessionCookie = $matches[1] ?? null;
            }

            $result['tests']['session_obtained'] = !empty($sessionCookie);

            if ($sessionCookie) {
                // Test accessing protected page with session
                $protectedResponse = Http::withOptions([
                    'verify' => false,
                    'timeout' => 30,
                    'allow_redirects' => false
                ])
                ->withHeaders([
                    'Cookie' => 'laravel_session=' . $sessionCookie
                ])
                ->get($this->baseUrl . '/admin');

                $result['tests']['protected_access'] = [
                    'status_code' => $protectedResponse->status(),
                    'accessible' => $protectedResponse->successful()
                ];
            }

            $result['status'] = $result['tests']['session_obtained'] ? 'PASS' : 'FAIL';

        } catch (\Exception $e) {
            $result['status'] = 'ERROR';
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Test logout flow
     */
    protected function testLogoutFlow(string $csrfToken)
    {
        $result = [
            'timestamp' => now()->toIso8601String(),
            'tests' => []
        ];

        try {
            // Login first to get session
            $loginResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'allow_redirects' => false
            ])
            ->asForm()
            ->post($this->baseUrl . '/login', [
                '_token' => $csrfToken,
                'email' => $this->testCredentials['super_admin']['email'],
                'password' => $this->testCredentials['super_admin']['password']
            ]);

            $sessionCookie = null;
            $setCookies = $loginResponse->header('Set-Cookie');
            if ($setCookies) {
                preg_match('/laravel_session=([^;]+)/', $setCookies, $matches);
                $sessionCookie = $matches[1] ?? null;
            }

            if ($sessionCookie) {
                // Perform logout
                $logoutResponse = Http::withOptions([
                    'verify' => false,
                    'timeout' => 30,
                    'allow_redirects' => false
                ])
                ->withHeaders([
                    'Cookie' => 'laravel_session=' . $sessionCookie
                ])
                ->asForm()
                ->post($this->baseUrl . '/logout', [
                    '_token' => $csrfToken
                ]);

                $result['tests']['logout_response'] = [
                    'status_code' => $logoutResponse->status(),
                    'is_redirect' => $logoutResponse->redirect(),
                    'redirects_to_home' => str_contains($logoutResponse->header('Location') ?? '', '/')
                ];

                // Verify session is invalidated
                $verifyResponse = Http::withOptions([
                    'verify' => false,
                    'timeout' => 30,
                    'allow_redirects' => false
                ])
                ->withHeaders([
                    'Cookie' => 'laravel_session=' . $sessionCookie
                ])
                ->get($this->baseUrl . '/admin');

                $result['tests']['session_invalidated'] = $verifyResponse->redirect();
            }

            $result['status'] = isset($result['tests']['session_invalidated']) &&
                                 $result['tests']['session_invalidated'] ? 'PASS' : 'FAIL';

        } catch (\Exception $e) {
            $result['status'] = 'ERROR';
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Test HTTPS enforcement
     */
    protected function testHttpsEnforcement()
    {
        $result = [
            'timestamp' => now()->toIso8601String(),
            'tests' => []
        ];

        try {
            // Test HTTP redirect to HTTPS
            $httpResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'allow_redirects' => false
            ])->get('http://ainstein.it/login');

            $result['tests']['http_request'] = [
                'status_code' => $httpResponse->status(),
                'is_redirect' => in_array($httpResponse->status(), [301, 302, 307, 308]),
                'location' => $httpResponse->header('Location')
            ];

            // Check if redirects to HTTPS
            $location = $httpResponse->header('Location') ?? '';
            $result['tests']['redirects_to_https'] = str_starts_with($location, 'https://');

            // Test direct HTTPS access
            $httpsResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'allow_redirects' => true
            ])->get($this->baseUrl . '/login');

            $result['tests']['https_accessible'] = $httpsResponse->successful();

            // Check for secure headers
            $result['tests']['secure_headers'] = [
                'strict_transport_security' => !empty($httpsResponse->header('Strict-Transport-Security')),
                'x_frame_options' => !empty($httpsResponse->header('X-Frame-Options')),
                'x_content_type_options' => !empty($httpsResponse->header('X-Content-Type-Options'))
            ];

            $result['status'] = $result['tests']['redirects_to_https'] &&
                                $result['tests']['https_accessible'] ? 'PASS' : 'FAIL';

        } catch (\Exception $e) {
            $result['status'] = 'ERROR';
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Generate test report
     */
    protected function generateReport(array $results)
    {
        $report = "PRODUCTION LOGIN TEST REPORT\n";
        $report .= "=" . str_repeat("=", 50) . "\n\n";
        $report .= "Timestamp: {$results['timestamp']}\n";
        $report .= "Environment: {$results['environment']}\n";
        $report .= "Base URL: {$results['base_url']}\n\n";

        $report .= "TEST RESULTS:\n";
        $report .= "-" . str_repeat("-", 50) . "\n\n";

        $passCount = 0;
        $failCount = 0;
        $errorCount = 0;

        foreach ($results['tests'] as $testName => $testResult) {
            $status = $testResult['status'] ?? 'UNKNOWN';

            switch ($status) {
                case 'PASS':
                    $passCount++;
                    $emoji = '✅';
                    break;
                case 'FAIL':
                    $failCount++;
                    $emoji = '❌';
                    break;
                case 'ERROR':
                    $errorCount++;
                    $emoji = '⚠️';
                    break;
                default:
                    $emoji = '❓';
            }

            $report .= "$emoji Test: " . str_replace('_', ' ', strtoupper($testName)) . "\n";
            $report .= "   Status: $status\n";

            if (isset($testResult['error'])) {
                $report .= "   Error: {$testResult['error']}\n";
            }

            $report .= "\n";
        }

        $report .= "SUMMARY:\n";
        $report .= "-" . str_repeat("-", 50) . "\n";
        $report .= "✅ Passed: $passCount\n";
        $report .= "❌ Failed: $failCount\n";
        $report .= "⚠️ Errors: $errorCount\n";
        $report .= "Total Tests: " . count($results['tests']) . "\n\n";

        $overallStatus = ($failCount === 0 && $errorCount === 0) ? 'SUCCESS' : 'FAILURE';
        $report .= "OVERALL STATUS: $overallStatus\n";

        // Save report
        $filename = storage_path('logs/production_login_test_' . date('Y-m-d_H-i-s') . '.txt');
        file_put_contents($filename, $report);

        Log::info("Test report saved to: $filename");
        echo $report;
    }
}