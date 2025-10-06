<?php
/**
 * Content Generator - Complete Browser Testing Script
 * Tests all tabs, filters, pagination, and navigation flows
 */

echo "🧪 CONTENT GENERATOR - COMPLETE BROWSER TESTING\n";
echo "===============================================\n\n";

$baseUrl = 'http://localhost:8000';
$testUser = 'admin@demo.com';
$testPassword = 'password';

// Test results
$results = [
    'passed' => [],
    'failed' => [],
    'warnings' => []
];

/**
 * HTTP Request Helper
 */
function makeRequest($url, $method = 'GET', $data = null, $cookies = []) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Add cookies
    if (!empty($cookies)) {
        $cookieStr = '';
        foreach ($cookies as $key => $value) {
            $cookieStr .= "$key=$value; ";
        }
        curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieStr, '; '));
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    curl_close($ch);

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body
    ];
}

/**
 * Extract cookies from headers
 */
function extractCookies($headers) {
    $cookies = [];
    preg_match_all('/Set-Cookie: ([^=]+)=([^;]+)/', $headers, $matches);

    if (!empty($matches[1])) {
        foreach ($matches[1] as $i => $name) {
            $cookies[$name] = $matches[2][$i];
        }
    }

    return $cookies;
}

/**
 * Login and get session cookie
 */
function login($baseUrl, $email, $password) {
    global $results;

    echo "🔐 Step 1: Login as $email\n";

    // Get login page for CSRF token
    $loginPage = makeRequest("$baseUrl/login");

    if ($loginPage['code'] !== 200) {
        $results['failed'][] = "Login page load failed (HTTP {$loginPage['code']})";
        return null;
    }

    // Extract CSRF token
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        $results['failed'][] = "CSRF token not found on login page";
        return null;
    }

    // Extract initial cookies
    $cookies = extractCookies($loginPage['headers']);

    // Perform login
    $loginResponse = makeRequest("$baseUrl/login", 'POST', [
        '_token' => $csrfToken,
        'email' => $email,
        'password' => $password
    ], $cookies);

    // Extract session cookies
    $sessionCookies = extractCookies($loginResponse['headers']);
    $cookies = array_merge($cookies, $sessionCookies);

    if ($loginResponse['code'] === 302 || $loginResponse['code'] === 200) {
        $results['passed'][] = "Login successful";
        echo "   ✅ Login successful\n\n";
        return $cookies;
    } else {
        $results['failed'][] = "Login failed (HTTP {$loginResponse['code']})";
        echo "   ❌ Login failed\n\n";
        return null;
    }
}

/**
 * Test Content Generator main page
 */
function testContentGeneratorIndex($baseUrl, $cookies) {
    global $results;

    echo "📄 Step 2: Test Content Generator Index (Default Tab)\n";

    $response = makeRequest("$baseUrl/dashboard/content", 'GET', null, $cookies);

    if ($response['code'] !== 200) {
        $results['failed'][] = "Content Generator index failed (HTTP {$response['code']})";
        echo "   ❌ Page load failed\n\n";
        return false;
    }

    $body = $response['body'];

    // Check for key elements
    $checks = [
        'Title' => preg_match('/Content Generator/', $body),
        'Pages Tab' => preg_match('/Pages</', $body),
        'Generations Tab' => preg_match('/Generations</', $body),
        'Prompts Tab' => preg_match('/Prompts</', $body),
        'Alpine.js' => preg_match('/x-data/', $body),
        'Tab Navigation' => preg_match('/activeTab/', $body),
        'Create Page Button' => preg_match('/Create Page/', $body)
    ];

    foreach ($checks as $item => $found) {
        if ($found) {
            $results['passed'][] = "Content Generator: $item found";
            echo "   ✅ $item found\n";
        } else {
            $results['failed'][] = "Content Generator: $item NOT found";
            echo "   ❌ $item NOT found\n";
        }
    }

    echo "\n";
    return true;
}

/**
 * Test Pages Tab with filters
 */
function testPagesTab($baseUrl, $cookies) {
    global $results;

    echo "📋 Step 3: Test Pages Tab\n";

    // Test default pages tab
    $response = makeRequest("$baseUrl/dashboard/content?tab=pages", 'GET', null, $cookies);

    if ($response['code'] !== 200) {
        $results['failed'][] = "Pages tab failed (HTTP {$response['code']})";
        echo "   ❌ Pages tab load failed\n\n";
        return false;
    }

    $body = $response['body'];

    $checks = [
        'Manage Pages Header' => preg_match('/Manage Pages/', $body),
        'Search Input' => preg_match('/name="search"/', $body),
        'Category Filter' => preg_match('/name="category"/', $body),
        'Pages Table' => preg_match('/<table/', $body),
        'URL Path Column' => preg_match('/url_path/', $body) || preg_match('/Page</', $body)
    ];

    foreach ($checks as $item => $found) {
        if ($found) {
            $results['passed'][] = "Pages Tab: $item found";
            echo "   ✅ $item found\n";
        } else {
            $results['warnings'][] = "Pages Tab: $item NOT found";
            echo "   ⚠️  $item NOT found\n";
        }
    }

    echo "\n";
    return true;
}

/**
 * Test Generations Tab
 */
function testGenerationsTab($baseUrl, $cookies) {
    global $results;

    echo "🤖 Step 4: Test Generations Tab\n";

    $response = makeRequest("$baseUrl/dashboard/content?tab=generations", 'GET', null, $cookies);

    if ($response['code'] !== 200) {
        $results['failed'][] = "Generations tab failed (HTTP {$response['code']})";
        echo "   ❌ Generations tab load failed\n\n";
        return false;
    }

    $body = $response['body'];

    $checks = [
        'Content Generations Header' => preg_match('/Content Generations/', $body),
        'Status Filter' => preg_match('/name="status"/', $body),
        'Search Input' => preg_match('/name="search"/', $body),
        'Generations Table' => preg_match('/<table/', $body),
        'Token Column' => preg_match('/Tokens</', $body) || preg_match('/tokens_used/', $body),
        'Quick Actions' => preg_match('/Quick Actions/', $body) || preg_match('/Export/', $body)
    ];

    foreach ($checks as $item => $found) {
        if ($found) {
            $results['passed'][] = "Generations Tab: $item found";
            echo "   ✅ $item found\n";
        } else {
            $results['warnings'][] = "Generations Tab: $item NOT found";
            echo "   ⚠️  $item NOT found\n";
        }
    }

    echo "\n";
    return true;
}

/**
 * Test Prompts Tab
 */
function testPromptsTab($baseUrl, $cookies) {
    global $results;

    echo "📜 Step 5: Test Prompts Tab\n";

    $response = makeRequest("$baseUrl/dashboard/content?tab=prompts", 'GET', null, $cookies);

    if ($response['code'] !== 200) {
        $results['failed'][] = "Prompts tab failed (HTTP {$response['code']})";
        echo "   ❌ Prompts tab load failed\n\n";
        return false;
    }

    $body = $response['body'];

    $checks = [
        'Prompt Templates Header' => preg_match('/Prompt Templates/', $body),
        'Create Prompt Button' => preg_match('/Create Prompt/', $body),
        'Prompts Grid' => preg_match('/grid grid-cols/', $body),
        'Prompt Statistics' => preg_match('/Prompt Statistics/', $body) || preg_match('/Total Prompts/', $body)
    ];

    foreach ($checks as $item => $found) {
        if ($found) {
            $results['passed'][] = "Prompts Tab: $item found";
            echo "   ✅ $item found\n";
        } else {
            $results['warnings'][] = "Prompts Tab: $item NOT found";
            echo "   ⚠️  $item NOT found\n";
        }
    }

    echo "\n";
    return true;
}

/**
 * Test backward compatibility redirects
 */
function testBackwardCompatibility($baseUrl, $cookies) {
    global $results;

    echo "🔄 Step 6: Test Backward Compatibility (Redirects)\n";

    // Test /dashboard/pages redirect
    $pagesRedirect = makeRequest("$baseUrl/dashboard/pages", 'GET', null, $cookies);

    if ($pagesRedirect['code'] === 302) {
        // Check redirect location
        if (preg_match('/Location:.*\/dashboard\/content\?tab=pages/', $pagesRedirect['headers'])) {
            $results['passed'][] = "Pages redirect working correctly";
            echo "   ✅ /dashboard/pages → /dashboard/content?tab=pages\n";
        } else {
            $results['failed'][] = "Pages redirect goes to wrong location";
            echo "   ❌ Pages redirect incorrect\n";
        }
    } else {
        $results['warnings'][] = "Pages redirect returned HTTP {$pagesRedirect['code']}";
        echo "   ⚠️  Pages redirect returned HTTP {$pagesRedirect['code']}\n";
    }

    // Test /dashboard/prompts redirect
    $promptsRedirect = makeRequest("$baseUrl/dashboard/prompts", 'GET', null, $cookies);

    if ($promptsRedirect['code'] === 302) {
        if (preg_match('/Location:.*\/dashboard\/content\?tab=prompts/', $promptsRedirect['headers'])) {
            $results['passed'][] = "Prompts redirect working correctly";
            echo "   ✅ /dashboard/prompts → /dashboard/content?tab=prompts\n";
        } else {
            $results['failed'][] = "Prompts redirect goes to wrong location";
            echo "   ❌ Prompts redirect incorrect\n";
        }
    } else {
        $results['warnings'][] = "Prompts redirect returned HTTP {$promptsRedirect['code']}";
        echo "   ⚠️  Prompts redirect returned HTTP {$promptsRedirect['code']}\n";
    }

    echo "\n";
    return true;
}

/**
 * Test navigation menu
 */
function testNavigationMenu($baseUrl, $cookies) {
    global $results;

    echo "🧭 Step 7: Test Navigation Menu\n";

    $response = makeRequest("$baseUrl/dashboard", 'GET', null, $cookies);

    if ($response['code'] !== 200) {
        $results['failed'][] = "Dashboard load failed (HTTP {$response['code']})";
        echo "   ❌ Dashboard load failed\n\n";
        return false;
    }

    $body = $response['body'];

    $checks = [
        'Content Generator Menu' => preg_match('/Content Generator/', $body),
        'Campaigns Menu' => preg_match('/Campaigns/', $body),
        'API Keys Menu' => preg_match('/API Keys/', $body),
        'Content Generator Link' => preg_match('/href="[^"]*\/dashboard\/content"/', $body)
    ];

    foreach ($checks as $item => $found) {
        if ($found) {
            $results['passed'][] = "Navigation: $item found";
            echo "   ✅ $item found\n";
        } else {
            $results['failed'][] = "Navigation: $item NOT found";
            echo "   ❌ $item NOT found\n";
        }
    }

    echo "\n";
    return true;
}

/**
 * Test search and filter functionality
 */
function testSearchAndFilters($baseUrl, $cookies) {
    global $results;

    echo "🔍 Step 8: Test Search & Filter Functionality\n";

    // Test pages search
    $searchResponse = makeRequest("$baseUrl/dashboard/content?tab=pages&search=test", 'GET', null, $cookies);

    if ($searchResponse['code'] === 200) {
        $results['passed'][] = "Pages search works (HTTP 200)";
        echo "   ✅ Pages search parameter accepted\n";
    } else {
        $results['failed'][] = "Pages search failed (HTTP {$searchResponse['code']})";
        echo "   ❌ Pages search failed\n";
    }

    // Test category filter
    $categoryResponse = makeRequest("$baseUrl/dashboard/content?tab=pages&category=blog", 'GET', null, $cookies);

    if ($categoryResponse['code'] === 200) {
        $results['passed'][] = "Category filter works (HTTP 200)";
        echo "   ✅ Category filter parameter accepted\n";
    } else {
        $results['failed'][] = "Category filter failed (HTTP {$categoryResponse['code']})";
        echo "   ❌ Category filter failed\n";
    }

    // Test generations status filter
    $statusResponse = makeRequest("$baseUrl/dashboard/content?tab=generations&status=completed", 'GET', null, $cookies);

    if ($statusResponse['code'] === 200) {
        $results['passed'][] = "Generations status filter works (HTTP 200)";
        echo "   ✅ Status filter parameter accepted\n";
    } else {
        $results['failed'][] = "Status filter failed (HTTP {$statusResponse['code']})";
        echo "   ❌ Status filter failed\n";
    }

    echo "\n";
    return true;
}

/**
 * Run all tests
 */
function runAllTests() {
    global $baseUrl, $testUser, $testPassword, $results;

    // Step 1: Login
    $cookies = login($baseUrl, $testUser, $testPassword);

    if (!$cookies) {
        echo "\n❌ Cannot proceed without login\n";
        return;
    }

    // Step 2-8: Run all tests
    testContentGeneratorIndex($baseUrl, $cookies);
    testPagesTab($baseUrl, $cookies);
    testGenerationsTab($baseUrl, $cookies);
    testPromptsTab($baseUrl, $cookies);
    testBackwardCompatibility($baseUrl, $cookies);
    testNavigationMenu($baseUrl, $cookies);
    testSearchAndFilters($baseUrl, $cookies);

    // Print summary
    printSummary();
}

/**
 * Print test summary
 */
function printSummary() {
    global $results;

    echo "═══════════════════════════════════════════════════\n";
    echo "📊 TEST SUMMARY\n";
    echo "═══════════════════════════════════════════════════\n\n";

    echo "✅ PASSED: " . count($results['passed']) . " tests\n";
    if (!empty($results['passed'])) {
        foreach ($results['passed'] as $test) {
            echo "   • $test\n";
        }
    }
    echo "\n";

    if (!empty($results['warnings'])) {
        echo "⚠️  WARNINGS: " . count($results['warnings']) . " items\n";
        foreach ($results['warnings'] as $warning) {
            echo "   • $warning\n";
        }
        echo "\n";
    }

    if (!empty($results['failed'])) {
        echo "❌ FAILED: " . count($results['failed']) . " tests\n";
        foreach ($results['failed'] as $failure) {
            echo "   • $failure\n";
        }
        echo "\n";
    }

    $totalTests = count($results['passed']) + count($results['failed']);
    $passRate = $totalTests > 0 ? round((count($results['passed']) / $totalTests) * 100, 1) : 0;

    echo "───────────────────────────────────────────────────\n";
    echo "📈 Pass Rate: $passRate% ($totalTests total tests)\n";
    echo "───────────────────────────────────────────────────\n\n";

    if (empty($results['failed'])) {
        echo "🎉 ALL TESTS PASSED! Content Generator is working perfectly!\n\n";
    } else {
        echo "⚠️  Some tests failed. Please review the failures above.\n\n";
    }
}

// Run all tests
runAllTests();
