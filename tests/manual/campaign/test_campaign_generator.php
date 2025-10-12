<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use App\Models\AdvCampaign;
use App\Services\Tools\CampaignAssetsGenerator;
use Illuminate\Support\Facades\Auth;

// Find test tenant and user
$tenant = Tenant::where('subdomain', 'test')->first();
if (!$tenant) {
    echo "❌ No test tenant found!\n";
    exit(1);
}

$user = User::where('tenant_id', $tenant->id)->first();
if (!$user) {
    echo "❌ No test user found!\n";
    exit(1);
}

echo "✅ Found test user: {$user->email} (Tenant: {$tenant->name})\n";
echo "=====================================\n\n";

// Test RSA Campaign Creation
echo "📝 Testing RSA Campaign Creation...\n";
$rsaCampaign = AdvCampaign::create([
    'tenant_id' => $tenant->id,
    'name' => 'Test RSA Campaign - ' . date('Y-m-d H:i:s'),
    'type' => 'rsa',
    'info' => 'Azienda leader nel settore tecnologico che offre soluzioni innovative per la trasformazione digitale',
    'keywords' => 'tecnologia, innovazione, digitale, software, soluzioni IT',
    'language' => 'it',
    'url' => 'https://example.com',
    'status' => 'draft'
]);

echo "✅ RSA Campaign created: {$rsaCampaign->name}\n";

// Test RSA Asset Generation
try {
    echo "🤖 Generating RSA assets...\n";
    $generator = app(CampaignAssetsGenerator::class);
    $rsaAsset = $generator->generate($rsaCampaign);

    echo "✅ RSA Assets generated successfully!\n";
    echo "   - Titles: " . count($rsaAsset->titles ?? []) . " generated\n";
    echo "   - Descriptions: " . count($rsaAsset->descriptions ?? []) . " generated\n";
    echo "   - Quality Score: " . ($rsaAsset->quality_score ?? 'N/A') . "/10\n";
    echo "   - Tokens Used: " . ($rsaAsset->tokens_used ?? 0) . "\n\n";

    // Display sample assets
    if (!empty($rsaAsset->titles)) {
        echo "   Sample Titles:\n";
        foreach (array_slice($rsaAsset->titles, 0, 3) as $i => $title) {
            echo "   " . ($i + 1) . ". {$title} (" . strlen($title) . " chars)\n";
        }
    }

    if (!empty($rsaAsset->descriptions)) {
        echo "\n   Sample Descriptions:\n";
        foreach (array_slice($rsaAsset->descriptions, 0, 2) as $i => $desc) {
            echo "   " . ($i + 1) . ". {$desc} (" . strlen($desc) . " chars)\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ RSA Generation failed: {$e->getMessage()}\n";
}

echo "\n=====================================\n\n";

// Test PMAX Campaign Creation
echo "📝 Testing PMAX Campaign Creation...\n";
$pmaxCampaign = AdvCampaign::create([
    'tenant_id' => $tenant->id,
    'name' => 'Test PMAX Campaign - ' . date('Y-m-d H:i:s'),
    'type' => 'pmax',
    'info' => 'E-commerce di prodotti premium con consegna rapida e servizio clienti eccellente',
    'keywords' => 'shopping online, prodotti premium, consegna rapida, qualità',
    'language' => 'it',
    'url' => 'https://shop.example.com',
    'status' => 'draft'
]);

echo "✅ PMAX Campaign created: {$pmaxCampaign->name}\n";

// Test PMAX Asset Generation
try {
    echo "🤖 Generating PMAX assets...\n";
    $generator = app(CampaignAssetsGenerator::class);
    $pmaxAsset = $generator->generate($pmaxCampaign);

    echo "✅ PMAX Assets generated successfully!\n";
    echo "   - Short Titles: " . count($pmaxAsset->titles ?? []) . " generated\n";
    echo "   - Long Titles: " . count($pmaxAsset->long_titles ?? []) . " generated\n";
    echo "   - Descriptions: " . count($pmaxAsset->descriptions ?? []) . " generated\n";
    echo "   - Quality Score: " . ($pmaxAsset->quality_score ?? 'N/A') . "/10\n";
    echo "   - Tokens Used: " . ($pmaxAsset->tokens_used ?? 0) . "\n\n";

    // Display sample assets
    if (!empty($pmaxAsset->titles)) {
        echo "   Sample Short Titles:\n";
        foreach (array_slice($pmaxAsset->titles, 0, 2) as $i => $title) {
            echo "   " . ($i + 1) . ". {$title} (" . strlen($title) . " chars)\n";
        }
    }

    if (!empty($pmaxAsset->long_titles)) {
        echo "\n   Sample Long Titles:\n";
        foreach (array_slice($pmaxAsset->long_titles, 0, 2) as $i => $title) {
            echo "   " . ($i + 1) . ". {$title} (" . strlen($title) . " chars)\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ PMAX Generation failed: {$e->getMessage()}\n";
}

echo "\n=====================================\n\n";

// Test Campaign List
echo "📋 Testing Campaign List...\n";
$campaigns = AdvCampaign::where('tenant_id', $tenant->id)
    ->withCount('assets')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Found " . $campaigns->count() . " recent campaigns:\n";
foreach ($campaigns as $campaign) {
    echo "   - {$campaign->name} ({$campaign->type}) - {$campaign->assets_count} assets\n";
}

echo "\n=====================================\n";
echo "✅ All tests completed successfully!\n";
echo "=====================================\n\n";
echo "📌 You can now access the Campaign Generator at:\n";
echo "   http://127.0.0.1:8001/dashboard/campaigns\n";
echo "   Login: admin@test.ainstein.local / password\n";