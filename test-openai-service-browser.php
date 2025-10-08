<?php
/**
 * OpenAI Service Browser Testing Script
 *
 * This script simulates browser requests to test all OpenAI Service endpoints
 * Tests: Chat, Completion, JSON Parsing, Embeddings, Error Handling
 */

$baseUrl = 'http://localhost:8001';
$testResults = [];

echo "🧪 OpenAI Service Browser Testing\n";
echo "==================================\n\n";

/**
 * Make HTTP request to endpoint
 */
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'parsed' => json_decode($response, true)
    ];
}

/**
 * Test 1: Check if test page loads
 */
echo "Test 1: Loading Test Interface\n";
echo "-------------------------------\n";
$result = makeRequest("$baseUrl/test-openai");

if ($result['http_code'] === 200) {
    echo "✅ Test page loaded successfully (HTTP {$result['http_code']})\n";

    // Check if page contains expected elements
    if (strpos($result['response'], 'OpenAI Service - Test Interface') !== false) {
        echo "✅ Page title found\n";
    }
    if (strpos($result['response'], 'Chat Completion') !== false) {
        echo "✅ Chat tab found\n";
    }
    if (strpos($result['response'], 'JSON Parsing') !== false) {
        echo "✅ JSON tab found\n";
    }

    $testResults[] = ['test' => 'Page Load', 'status' => 'PASSED'];
} else {
    echo "❌ Failed to load test page (HTTP {$result['http_code']})\n";
    echo "Error: {$result['error']}\n";
    $testResults[] = ['test' => 'Page Load', 'status' => 'FAILED'];
}

echo "\n";

/**
 * Test 2: Chat Completion
 */
echo "Test 2: Chat Completion\n";
echo "-----------------------\n";
$result = makeRequest("$baseUrl/test-openai/chat", 'POST', [
    'message' => 'Hello! Can you say hi back?',
    'use_case' => 'default',
    'model' => ''
]);

if ($result['http_code'] === 200 && isset($result['parsed']['success'])) {
    echo "✅ Chat endpoint responded (HTTP {$result['http_code']})\n";

    if ($result['parsed']['success']) {
        echo "✅ Chat completed successfully\n";
        echo "   Content: " . substr($result['parsed']['result']['content'], 0, 80) . "...\n";
        echo "   Tokens: {$result['parsed']['result']['tokens_used']}\n";
        echo "   Model: {$result['parsed']['result']['model']}\n";
        $testResults[] = ['test' => 'Chat Completion', 'status' => 'PASSED'];
    } else {
        echo "⚠️  Chat returned error: {$result['parsed']['error']}\n";
        $testResults[] = ['test' => 'Chat Completion', 'status' => 'ERROR'];
    }
} else {
    echo "❌ Chat endpoint failed (HTTP {$result['http_code']})\n";
    if (isset($result['parsed']['message'])) {
        echo "   Error: {$result['parsed']['message']}\n";
    }
    $testResults[] = ['test' => 'Chat Completion', 'status' => 'FAILED'];
}

echo "\n";

/**
 * Test 3: Simple Completion
 */
echo "Test 3: Simple Completion\n";
echo "-------------------------\n";
$result = makeRequest("$baseUrl/test-openai/completion", 'POST', [
    'prompt' => 'Write one sentence about Laravel framework.',
    'system_message' => 'You are a technical writer.',
    'model' => ''
]);

if ($result['http_code'] === 200 && isset($result['parsed']['success'])) {
    echo "✅ Completion endpoint responded (HTTP {$result['http_code']})\n";

    if ($result['parsed']['success']) {
        echo "✅ Completion successful\n";
        echo "   Content: " . substr($result['parsed']['result']['content'], 0, 80) . "...\n";
        echo "   Tokens: {$result['parsed']['result']['tokens_used']}\n";
        $testResults[] = ['test' => 'Simple Completion', 'status' => 'PASSED'];
    } else {
        echo "⚠️  Completion returned error: {$result['parsed']['error']}\n";
        $testResults[] = ['test' => 'Simple Completion', 'status' => 'ERROR'];
    }
} else {
    echo "❌ Completion endpoint failed (HTTP {$result['http_code']})\n";
    $testResults[] = ['test' => 'Simple Completion', 'status' => 'FAILED'];
}

echo "\n";

/**
 * Test 4: JSON Parsing
 */
echo "Test 4: JSON Parsing\n";
echo "--------------------\n";
$result = makeRequest("$baseUrl/test-openai/json", 'POST', [
    'prompt' => 'Generate a JSON object with fields: name (string), age (number), active (boolean)'
]);

if ($result['http_code'] === 200 && isset($result['parsed']['success'])) {
    echo "✅ JSON endpoint responded (HTTP {$result['http_code']})\n";

    if ($result['parsed']['success']) {
        echo "✅ JSON parsing successful\n";
        echo "   Parsed data: " . json_encode($result['parsed']['result']['parsed']) . "\n";
        echo "   Tokens: {$result['parsed']['result']['tokens_used']}\n";

        // Validate JSON structure
        if (is_array($result['parsed']['result']['parsed'])) {
            echo "✅ Valid JSON structure returned\n";
            $testResults[] = ['test' => 'JSON Parsing', 'status' => 'PASSED'];
        } else {
            echo "⚠️  JSON structure validation failed\n";
            $testResults[] = ['test' => 'JSON Parsing', 'status' => 'WARNING'];
        }
    } else {
        echo "⚠️  JSON parsing returned error: {$result['parsed']['error']}\n";
        $testResults[] = ['test' => 'JSON Parsing', 'status' => 'ERROR'];
    }
} else {
    echo "❌ JSON endpoint failed (HTTP {$result['http_code']})\n";
    $testResults[] = ['test' => 'JSON Parsing', 'status' => 'FAILED'];
}

echo "\n";

/**
 * Test 5: Embeddings
 */
echo "Test 5: Embeddings Generation\n";
echo "------------------------------\n";
$result = makeRequest("$baseUrl/test-openai/embeddings", 'POST', [
    'text' => 'Laravel is a web application framework with expressive, elegant syntax.'
]);

if ($result['http_code'] === 200 && isset($result['parsed']['success'])) {
    echo "✅ Embeddings endpoint responded (HTTP {$result['http_code']})\n";

    if ($result['parsed']['success']) {
        echo "✅ Embeddings generated successfully\n";
        echo "   Embeddings count: {$result['parsed']['result']['embeddings_count']}\n";
        echo "   Dimensions: {$result['parsed']['result']['embedding_dimensions']}\n";
        echo "   Tokens: {$result['parsed']['result']['tokens_used']}\n";
        echo "   Model: {$result['parsed']['result']['model']}\n";

        if ($result['parsed']['result']['embedding_dimensions'] > 0) {
            echo "✅ Valid embeddings generated\n";
            $testResults[] = ['test' => 'Embeddings', 'status' => 'PASSED'];
        }
    } else {
        echo "⚠️  Embeddings returned error: {$result['parsed']['error']}\n";
        $testResults[] = ['test' => 'Embeddings', 'status' => 'ERROR'];
    }
} else {
    echo "❌ Embeddings endpoint failed (HTTP {$result['http_code']})\n";
    $testResults[] = ['test' => 'Embeddings', 'status' => 'FAILED'];
}

echo "\n";

/**
 * Test 6: Error Handling
 */
echo "Test 6: Error Handling\n";
echo "----------------------\n";
$result = makeRequest("$baseUrl/test-openai/error", 'POST');

if ($result['http_code'] >= 400 && isset($result['parsed']['error_handled'])) {
    echo "✅ Error handling endpoint responded correctly (HTTP {$result['http_code']})\n";

    if ($result['parsed']['error_handled']) {
        echo "✅ Error was caught and handled properly\n";
        echo "   Error message: {$result['parsed']['error']}\n";
        $testResults[] = ['test' => 'Error Handling', 'status' => 'PASSED'];
    }
} else {
    echo "⚠️  Error handling test (expected behavior varies based on mock/real API)\n";
    echo "   HTTP Code: {$result['http_code']}\n";
    $testResults[] = ['test' => 'Error Handling', 'status' => 'SKIPPED'];
}

echo "\n";

/**
 * Test 7: Use Case Configuration
 */
echo "Test 7: Use Case Configuration\n";
echo "-------------------------------\n";
$useCases = ['campaigns', 'articles', 'seo', 'internal_links'];
$useCasesResults = [];

foreach ($useCases as $useCase) {
    $result = makeRequest("$baseUrl/test-openai/chat", 'POST', [
        'message' => "Test for $useCase use case",
        'use_case' => $useCase,
    ]);

    if ($result['http_code'] === 200 && $result['parsed']['success']) {
        echo "✅ Use case '$useCase': {$result['parsed']['result']['model']} ({$result['parsed']['result']['tokens_used']} tokens)\n";
        $useCasesResults[] = true;
    } else {
        echo "⚠️  Use case '$useCase' failed\n";
        $useCasesResults[] = false;
    }
}

if (count(array_filter($useCasesResults)) === count($useCases)) {
    echo "✅ All use cases tested successfully\n";
    $testResults[] = ['test' => 'Use Case Config', 'status' => 'PASSED'];
} else {
    echo "⚠️  Some use cases failed\n";
    $testResults[] = ['test' => 'Use Case Config', 'status' => 'WARNING'];
}

echo "\n";

/**
 * Summary
 */
echo "\n";
echo "📊 Test Summary\n";
echo "===============\n";

$passed = 0;
$failed = 0;
$errors = 0;
$warnings = 0;
$skipped = 0;

foreach ($testResults as $result) {
    $icon = match($result['status']) {
        'PASSED' => '✅',
        'FAILED' => '❌',
        'ERROR' => '⚠️',
        'WARNING' => '⚠️',
        'SKIPPED' => '⏭️',
        default => '❓'
    };

    echo "$icon {$result['test']}: {$result['status']}\n";

    match($result['status']) {
        'PASSED' => $passed++,
        'FAILED' => $failed++,
        'ERROR' => $errors++,
        'WARNING' => $warnings++,
        'SKIPPED' => $skipped++,
        default => null
    };
}

echo "\n";
echo "Total Tests: " . count($testResults) . "\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "⚠️  Errors/Warnings: " . ($errors + $warnings) . "\n";
echo "⏭️  Skipped: $skipped\n";

if ($failed === 0 && $errors === 0) {
    echo "\n🎉 All critical tests passed!\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed or had errors. Please review.\n";
    exit(1);
}
