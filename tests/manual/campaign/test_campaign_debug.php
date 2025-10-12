<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OpenAiService;

// Test the parseJSON method directly
$openAiService = app(OpenAiService::class);

echo "Testing OpenAiService->parseJSON for RSA campaign...\n";

$rsaPrompt = "Generate RSA campaign assets in Italian";
$rsaVariables = [
    'business_info' => 'Azienda tech innovativa',
    'keywords' => 'tecnologia, innovazione, digitale'
];

try {
    $rsaResult = $openAiService->parseJSON($rsaPrompt, $rsaVariables);
    echo "RSA Result:\n";
    print_r($rsaResult);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTesting OpenAiService->parseJSON for PMAX campaign...\n";

$pmaxPrompt = "Generate PMAX campaign assets in Italian";
$pmaxVariables = [
    'business_info' => 'E-commerce premium',
    'keywords' => 'shopping, qualità, convenienza'
];

try {
    $pmaxResult = $openAiService->parseJSON($pmaxPrompt, $pmaxVariables);
    echo "PMAX Result:\n";
    print_r($pmaxResult);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}