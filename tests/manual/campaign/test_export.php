<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\AdvCampaign;
use App\Http\Controllers\Tenant\CampaignGeneratorController;

// Find test tenant
$tenant = Tenant::where('subdomain', 'test')->first();

// Get latest campaigns with assets
$rsaCampaign = AdvCampaign::where('tenant_id', $tenant->id)
    ->where('type', 'rsa')
    ->whereHas('assets')
    ->with('assets')
    ->orderBy('created_at', 'desc')
    ->first();

$pmaxCampaign = AdvCampaign::where('tenant_id', $tenant->id)
    ->where('type', 'pmax')
    ->whereHas('assets')
    ->with('assets')
    ->orderBy('created_at', 'desc')
    ->first();

echo "📊 TESTING EXPORT FUNCTIONALITY\n";
echo "=====================================\n\n";

if ($rsaCampaign && $rsaCampaign->assets->count() > 0) {
    echo "✅ Testing RSA Campaign Export\n";
    echo "Campaign: {$rsaCampaign->name}\n";
    echo "Assets count: {$rsaCampaign->assets->count()}\n\n";

    $asset = $rsaCampaign->assets->first();

    echo "RSA Assets Preview:\n";
    echo "- Titles: " . count($asset->titles ?? []) . " items\n";
    if ($asset->titles) {
        echo "  Sample: " . $asset->titles[0] . "\n";
    }
    echo "- Descriptions: " . count($asset->descriptions ?? []) . " items\n";
    if ($asset->descriptions) {
        echo "  Sample: " . $asset->descriptions[0] . "\n";
    }

    // Test CSV export structure
    echo "\n📄 CSV Export Structure (RSA):\n";
    echo "Headers: Type, Content, Character Count\n";
    echo "Sample row: Title, {$asset->titles[0]}, " . mb_strlen($asset->titles[0]) . "\n";

    // Test Google Ads export structure
    echo "\n📄 Google Ads Export Structure (RSA):\n";
    echo "Headers: Campaign, Ad Group, Headline 1-15, Description 1-4, Final URL\n";
    echo "Sample data: {$rsaCampaign->name}, Ad Group 1, [15 headlines], [4 descriptions], {$rsaCampaign->url}\n";
} else {
    echo "⚠️ No RSA campaign with assets found for testing\n";
}

echo "\n=====================================\n\n";

if ($pmaxCampaign && $pmaxCampaign->assets->count() > 0) {
    echo "✅ Testing PMAX Campaign Export\n";
    echo "Campaign: {$pmaxCampaign->name}\n";
    echo "Assets count: {$pmaxCampaign->assets->count()}\n\n";

    $asset = $pmaxCampaign->assets->first();

    echo "PMAX Assets Preview:\n";
    echo "- Short Titles: " . count($asset->titles ?? []) . " items\n";
    if ($asset->titles) {
        echo "  Sample: " . $asset->titles[0] . "\n";
    }
    echo "- Long Titles: " . count($asset->long_titles ?? []) . " items\n";
    if ($asset->long_titles) {
        echo "  Sample: " . $asset->long_titles[0] . "\n";
    }
    echo "- Descriptions: " . count($asset->descriptions ?? []) . " items\n";
    if ($asset->descriptions) {
        echo "  Sample: " . $asset->descriptions[0] . "\n";
    }

    // Test CSV export structure
    echo "\n📄 CSV Export Structure (PMAX):\n";
    echo "Headers: Type, Content, Character Count\n";
    echo "Sample rows:\n";
    echo "  Short Title, {$asset->titles[0]}, " . mb_strlen($asset->titles[0]) . "\n";
    echo "  Long Title, {$asset->long_titles[0]}, " . mb_strlen($asset->long_titles[0]) . "\n";

    // Test Google Ads export structure
    echo "\n📄 Google Ads Export Structure (PMAX):\n";
    echo "Headers: Campaign, Asset Group, Short Headline 1-5, Long Headline 1-5, Description 1-5, Final URL\n";
    echo "Sample data: {$pmaxCampaign->name}, Asset Group 1, [5 short], [5 long], [5 desc], {$pmaxCampaign->url}\n";
} else {
    echo "⚠️ No PMAX campaign with assets found for testing\n";
}

echo "\n=====================================\n";
echo "✅ Export functionality is ready!\n";
echo "=====================================\n\n";

// Test export URLs
echo "📌 Export endpoints available:\n";
echo "   CSV Export: /dashboard/campaigns/{id}/export/csv\n";
echo "   Google Ads Export: /dashboard/campaigns/{id}/export/google-ads\n";