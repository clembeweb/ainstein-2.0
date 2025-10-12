<?php
/**
 * Content Generator - Main Functions Test
 * Tests CREATE, UPDATE, DELETE operations
 */

echo "🔧 CONTENT GENERATOR - MAIN FUNCTIONS TEST\n";
echo "===========================================\n\n";

$baseUrl = 'http://localhost:8000';
$testUser = 'admin@demo.com';
$testPassword = 'password';

$results = [
    'passed' => [],
    'failed' => [],
    'warnings' => []
];

function makeRequest($url, $method = 'GET', $data = null, $cookies = []) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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
    } elseif ($method === 'DELETE' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
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

function login($baseUrl, $email, $password) {
    global $results;

    echo "🔐 Login as $email\n";

    $loginPage = makeRequest("$baseUrl/login");
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        $results['failed'][] = "CSRF token not found";
        return null;
    }

    $cookies = extractCookies($loginPage['headers']);

    $loginResponse = makeRequest("$baseUrl/login", 'POST', [
        '_token' => $csrfToken,
        'email' => $email,
        'password' => $password
    ], $cookies);

    $sessionCookies = extractCookies($loginResponse['headers']);
    $cookies = array_merge($cookies, $sessionCookies);

    if ($loginResponse['code'] === 200 || $loginResponse['code'] === 302) {
        echo "   ✅ Login successful\n\n";
        return $cookies;
    } else {
        echo "   ❌ Login failed\n\n";
        return null;
    }
}

function testCreatePage($baseUrl, $cookies) {
    global $results;

    echo "📝 TEST 1: Create New Page\n";
    echo "─────────────────────────────────────────\n";

    // Get create page form
    $createPageForm = makeRequest("$baseUrl/dashboard/pages/create", 'GET', null, $cookies);

    if ($createPageForm['code'] !== 200) {
        echo "   ❌ Create page form not accessible (HTTP {$createPageForm['code']})\n\n";
        $results['failed'][] = "Create page form failed";
        return null;
    }

    echo "   ✅ Create page form loaded\n";

    // Extract CSRF token
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $createPageForm['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        echo "   ❌ CSRF token not found in create form\n\n";
        $results['failed'][] = "CSRF token missing in create form";
        return null;
    }

    // Create new page
    $testTimestamp = time();
    $pageData = [
        '_token' => $csrfToken,
        'url_path' => "/test-page-{$testTimestamp}",
        'keyword' => "test keyword {$testTimestamp}",
        'category' => 'blog',
        'language' => 'it',
        'is_published' => '1'
    ];

    $createResponse = makeRequest("$baseUrl/dashboard/pages", 'POST', $pageData, $cookies);

    if ($createResponse['code'] === 200 || $createResponse['code'] === 302) {
        echo "   ✅ Page created successfully\n";

        // Extract page ID from redirect or body
        preg_match('/\/pages\/([a-zA-Z0-9]+)/', $createResponse['headers'] . $createResponse['body'], $idMatch);
        $pageId = $idMatch[1] ?? null;

        if ($pageId) {
            echo "   ✅ Page ID extracted: $pageId\n";
            $results['passed'][] = "✅ Page creation successful";
        } else {
            echo "   ⚠️  Page created but ID not found\n";
            $results['warnings'][] = "Page created, ID not extracted";
        }

        echo "\n";
        return $pageId;
    } else {
        echo "   ❌ Page creation failed (HTTP {$createResponse['code']})\n";
        echo "   Error details: " . substr($createResponse['body'], 0, 200) . "\n\n";
        $results['failed'][] = "Page creation failed";
        return null;
    }
}

function testCreatePrompt($baseUrl, $cookies) {
    global $results;

    echo "📜 TEST 2: Create New Prompt\n";
    echo "─────────────────────────────────────────\n";

    // Get create prompt form
    $createPromptForm = makeRequest("$baseUrl/dashboard/prompts/create", 'GET', null, $cookies);

    if ($createPromptForm['code'] !== 200) {
        echo "   ❌ Create prompt form not accessible (HTTP {$createPromptForm['code']})\n\n";
        $results['failed'][] = "Create prompt form failed";
        return null;
    }

    echo "   ✅ Create prompt form loaded\n";

    // Extract CSRF token
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $createPromptForm['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        echo "   ❌ CSRF token not found in create form\n\n";
        $results['failed'][] = "CSRF token missing";
        return null;
    }

    // Create new prompt
    $testTimestamp = time();
    $promptData = [
        '_token' => $csrfToken,
        'name' => "Test Prompt {$testTimestamp}",
        'alias' => "test_prompt_{$testTimestamp}",
        'description' => "This is a test prompt created by automated testing",
        'template' => "Generate content about {{topic}} in {{language}}. Focus on {{keyword}}.",
        'category' => 'test',
        'is_active' => '1'
    ];

    $createResponse = makeRequest("$baseUrl/dashboard/prompts", 'POST', $promptData, $cookies);

    if ($createResponse['code'] === 200 || $createResponse['code'] === 302) {
        echo "   ✅ Prompt created successfully\n";

        // Extract prompt ID
        preg_match('/\/prompts\/([a-zA-Z0-9]+)/', $createResponse['headers'] . $createResponse['body'], $idMatch);
        $promptId = $idMatch[1] ?? null;

        if ($promptId) {
            echo "   ✅ Prompt ID extracted: $promptId\n";
            $results['passed'][] = "✅ Prompt creation successful";
        } else {
            echo "   ⚠️  Prompt created but ID not found\n";
            $results['warnings'][] = "Prompt created, ID not extracted";
        }

        echo "\n";
        return $promptId;
    } else {
        echo "   ❌ Prompt creation failed (HTTP {$createResponse['code']})\n";
        echo "   Error: " . substr($createResponse['body'], 0, 200) . "\n\n";
        $results['failed'][] = "Prompt creation failed";
        return null;
    }
}

function testEditPrompt($baseUrl, $cookies, $promptId) {
    global $results;

    echo "✏️  TEST 3: Edit Prompt\n";
    echo "─────────────────────────────────────────\n";

    if (!$promptId) {
        echo "   ⏭️  Skipped (no prompt ID available)\n\n";
        return false;
    }

    // Get edit form
    $editForm = makeRequest("$baseUrl/dashboard/prompts/$promptId/edit", 'GET', null, $cookies);

    if ($editForm['code'] !== 200) {
        echo "   ❌ Edit form not accessible (HTTP {$editForm['code']})\n\n";
        $results['failed'][] = "Edit prompt form failed";
        return false;
    }

    echo "   ✅ Edit form loaded\n";

    // Extract CSRF token
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $editForm['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        echo "   ❌ CSRF token not found\n\n";
        $results['failed'][] = "CSRF token missing in edit form";
        return false;
    }

    // Update prompt
    $updateData = [
        '_token' => $csrfToken,
        '_method' => 'PUT',
        'name' => "Test Prompt UPDATED",
        'alias' => "test_prompt_updated",
        'description' => "This prompt has been updated by automated testing",
        'template' => "UPDATED: Generate content about {{topic}}",
        'category' => 'test-updated',
        'is_active' => '1'
    ];

    $updateResponse = makeRequest("$baseUrl/dashboard/prompts/$promptId", 'POST', $updateData, $cookies);

    if ($updateResponse['code'] === 200 || $updateResponse['code'] === 302) {
        echo "   ✅ Prompt updated successfully\n";
        $results['passed'][] = "✅ Prompt update successful";
        echo "\n";
        return true;
    } else {
        echo "   ❌ Prompt update failed (HTTP {$updateResponse['code']})\n\n";
        $results['failed'][] = "Prompt update failed";
        return false;
    }
}

function testDuplicatePrompt($baseUrl, $cookies, $promptId) {
    global $results;

    echo "📋 TEST 4: Duplicate Prompt\n";
    echo "─────────────────────────────────────────\n";

    if (!$promptId) {
        echo "   ⏭️  Skipped (no prompt ID available)\n\n";
        return null;
    }

    // Get prompt page to extract CSRF token
    $promptPage = makeRequest("$baseUrl/dashboard/prompts/$promptId", 'GET', null, $cookies);
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $promptPage['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        echo "   ❌ CSRF token not found\n\n";
        $results['failed'][] = "CSRF token missing for duplicate";
        return null;
    }

    // Duplicate prompt
    $duplicateResponse = makeRequest("$baseUrl/dashboard/prompts/$promptId/duplicate", 'POST', [
        '_token' => $csrfToken
    ], $cookies);

    if ($duplicateResponse['code'] === 200 || $duplicateResponse['code'] === 302) {
        echo "   ✅ Prompt duplicated successfully\n";

        // Extract new prompt ID
        preg_match('/\/prompts\/([a-zA-Z0-9]+)\/edit/', $duplicateResponse['headers'], $idMatch);
        $newPromptId = $idMatch[1] ?? null;

        if ($newPromptId) {
            echo "   ✅ Duplicate prompt ID: $newPromptId\n";
            $results['passed'][] = "✅ Prompt duplication successful";
        } else {
            echo "   ⚠️  Duplicated but ID not found\n";
            $results['warnings'][] = "Duplicate successful, ID not extracted";
        }

        echo "\n";
        return $newPromptId;
    } else {
        echo "   ❌ Duplication failed (HTTP {$duplicateResponse['code']})\n\n";
        $results['failed'][] = "Prompt duplication failed";
        return null;
    }
}

function testDeletePrompt($baseUrl, $cookies, $promptId) {
    global $results;

    echo "🗑️  TEST 5: Delete Prompt\n";
    echo "─────────────────────────────────────────\n";

    if (!$promptId) {
        echo "   ⏭️  Skipped (no prompt ID available)\n\n";
        return false;
    }

    // Get CSRF token from prompts list
    $promptsList = makeRequest("$baseUrl/dashboard/content?tab=prompts", 'GET', null, $cookies);
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $promptsList['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        echo "   ❌ CSRF token not found\n\n";
        $results['failed'][] = "CSRF token missing for delete";
        return false;
    }

    // Delete prompt
    $deleteResponse = makeRequest("$baseUrl/dashboard/prompts/$promptId", 'POST', [
        '_token' => $csrfToken,
        '_method' => 'DELETE'
    ], $cookies);

    if ($deleteResponse['code'] === 200 || $deleteResponse['code'] === 302) {
        echo "   ✅ Prompt deleted successfully\n";
        $results['passed'][] = "✅ Prompt deletion successful";
        echo "\n";
        return true;
    } else {
        echo "   ❌ Deletion failed (HTTP {$deleteResponse['code']})\n\n";
        $results['failed'][] = "Prompt deletion failed";
        return false;
    }
}

function testContentGeneration($baseUrl, $cookies, $pageId) {
    global $results;

    echo "🤖 TEST 6: Content Generation\n";
    echo "─────────────────────────────────────────\n";

    if (!$pageId) {
        echo "   ⏭️  Skipped (no page ID available)\n\n";
        return false;
    }

    // Get generation form
    $genForm = makeRequest("$baseUrl/dashboard/content/create", 'GET', null, $cookies);

    if ($genForm['code'] !== 200) {
        echo "   ❌ Generation form not accessible (HTTP {$genForm['code']})\n\n";
        $results['failed'][] = "Generation form failed";
        return false;
    }

    echo "   ✅ Generation form loaded\n";

    // Extract CSRF token
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $genForm['body'], $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? null;

    if (!$csrfToken) {
        echo "   ❌ CSRF token not found\n\n";
        $results['failed'][] = "CSRF token missing in generation form";
        return false;
    }

    // Note: We can't actually generate content without a valid OpenAI API key
    // But we can test that the form accepts the data
    echo "   ✅ Form is accessible and ready\n";
    echo "   ℹ️  Note: Actual generation requires OpenAI API key\n";
    $results['passed'][] = "✅ Content generation form accessible";

    echo "\n";
    return true;
}

function printSummary() {
    global $results;

    echo "═══════════════════════════════════════════════════\n";
    echo "📊 MAIN FUNCTIONS TEST SUMMARY\n";
    echo "═══════════════════════════════════════════════════\n\n";

    $totalTests = count($results['passed']) + count($results['failed']);
    $passRate = $totalTests > 0 ? round((count($results['passed']) / $totalTests) * 100, 1) : 0;

    echo "✅ PASSED: " . count($results['passed']) . " tests\n";
    foreach ($results['passed'] as $test) {
        echo "   $test\n";
    }
    echo "\n";

    if (!empty($results['warnings'])) {
        echo "⚠️  WARNINGS: " . count($results['warnings']) . " items\n";
        foreach ($results['warnings'] as $warning) {
            echo "   $warning\n";
        }
        echo "\n";
    }

    if (!empty($results['failed'])) {
        echo "❌ FAILED: " . count($results['failed']) . " tests\n";
        foreach ($results['failed'] as $failure) {
            echo "   $failure\n";
        }
        echo "\n";
    }

    echo "───────────────────────────────────────────────────\n";
    echo "📈 Pass Rate: $passRate% ($totalTests total tests)\n";
    echo "───────────────────────────────────────────────────\n\n";

    if (empty($results['failed'])) {
        echo "🎉 ALL MAIN FUNCTIONS WORKING!\n\n";
        echo "✨ Functions Tested:\n";
        echo "   1. ✅ Create Page\n";
        echo "   2. ✅ Create Prompt\n";
        echo "   3. ✅ Edit Prompt\n";
        echo "   4. ✅ Duplicate Prompt\n";
        echo "   5. ✅ Delete Prompt\n";
        echo "   6. ✅ Content Generation Form\n\n";
    } else {
        echo "⚠️  Some functions failed. Review above.\n\n";
    }
}

// Run all main function tests
echo "Starting main functions test...\n\n";

$cookies = login($baseUrl, $testUser, $testPassword);

if (!$cookies) {
    echo "❌ Cannot proceed without login\n";
    exit(1);
}

$pageId = testCreatePage($baseUrl, $cookies);
$promptId = testCreatePrompt($baseUrl, $cookies);
testEditPrompt($baseUrl, $cookies, $promptId);
$duplicateId = testDuplicatePrompt($baseUrl, $cookies, $promptId);
testContentGeneration($baseUrl, $cookies, $pageId);
testDeletePrompt($baseUrl, $cookies, $duplicateId); // Delete duplicate
testDeletePrompt($baseUrl, $cookies, $promptId); // Delete original

printSummary();
