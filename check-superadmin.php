<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "🔍 CHECKING SUPER ADMIN ACCOUNTS\n";
echo str_repeat('=', 80) . "\n\n";

// Find all super admins
$superAdmins = App\Models\User::where('is_super_admin', true)->get();

if ($superAdmins->isEmpty()) {
    echo "⚠️  No super admin accounts found!\n";
    echo "Creating a super admin account...\n\n";

    // Create super admin
    $admin = App\Models\User::create([
        'name' => 'Super Admin',
        'email' => 'admin@ainstein.com',
        'password_hash' => bcrypt('admin123'),
        'is_super_admin' => true,
        'role' => 'super_admin',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    echo "✅ Super admin created:\n";
    echo "   Email: admin@ainstein.com\n";
    echo "   Password: admin123\n";
    echo "   ID: {$admin->id}\n\n";
} else {
    echo "✅ Found " . $superAdmins->count() . " super admin account(s):\n\n";

    foreach ($superAdmins as $admin) {
        echo "📋 Super Admin:\n";
        echo "   Email: {$admin->email}\n";
        echo "   Name: {$admin->name}\n";
        echo "   ID: {$admin->id}\n";
        echo "   Active: " . ($admin->is_active ? 'Yes' : 'No') . "\n";
        echo "   Tenant: " . ($admin->tenant_id ? 'Has tenant' : 'No tenant') . "\n";
        echo "\n";
    }
}

echo str_repeat('=', 80) . "\n";
echo "🔐 ADMIN LOGIN ROUTE\n";
echo str_repeat('=', 80) . "\n\n";

// Check admin routes
$routes = Artisan::call('route:list', [
    '--path' => 'admin',
]);

$output = Artisan::output();

if (strpos($output, '/admin') !== false) {
    echo "✅ Admin routes found\n\n";
    echo "Admin panel access:\n";
    echo "   URL: http://127.0.0.1:8080/admin\n";
    echo "   Login: http://127.0.0.1:8080/admin/login\n";
} else {
    echo "⚠️  No /admin routes found. Checking Filament...\n\n";
}

echo "\n";
echo str_repeat('=', 80) . "\n";
