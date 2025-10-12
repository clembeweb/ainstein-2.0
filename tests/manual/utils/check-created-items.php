<?php

require __DIR__ . '/ainstein-laravel/vendor/autoload.php';

$app = require_once __DIR__ . '/ainstein-laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 CHECKING CREATED ITEMS\n";
echo "=========================\n\n";

// Check recent pages
echo "📄 Recent Pages (last 5):\n";
echo "─────────────────────────────────────────\n";
$pages = App\Models\Content::latest()->take(5)->get(['id', 'url_path', 'keyword', 'created_at']);

if ($pages->count() > 0) {
    foreach ($pages as $page) {
        echo "  ✅ ID: {$page->id}\n";
        echo "     URL: {$page->url_path}\n";
        echo "     Keyword: {$page->keyword}\n";
        echo "     Created: {$page->created_at->format('Y-m-d H:i:s')}\n";
        echo "\n";
    }
} else {
    echo "  ❌ No pages found\n\n";
}

// Check recent prompts (non-system)
echo "📜 Recent Custom Prompts (last 5):\n";
echo "─────────────────────────────────────────\n";
$prompts = App\Models\Prompt::where('is_system', false)
    ->latest()
    ->take(5)
    ->get(['id', 'name', 'alias', 'created_at']);

if ($prompts->count() > 0) {
    foreach ($prompts as $prompt) {
        echo "  ✅ ID: {$prompt->id}\n";
        echo "     Name: {$prompt->name}\n";
        echo "     Alias: {$prompt->alias}\n";
        echo "     Created: {$prompt->created_at->format('Y-m-d H:i:s')}\n";
        echo "\n";
    }
} else {
    echo "  ❌ No custom prompts found\n\n";
}

// Check today's items
$today = today();
echo "📅 Items Created Today:\n";
echo "─────────────────────────────────────────\n";

$todayPages = App\Models\Content::whereDate('created_at', $today)->count();
$todayPrompts = App\Models\Prompt::where('is_system', false)
    ->whereDate('created_at', $today)
    ->count();

echo "  Pages: {$todayPages}\n";
echo "  Prompts: {$todayPrompts}\n\n";

echo "✅ Check complete!\n";
