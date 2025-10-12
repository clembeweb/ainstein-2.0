<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTING SUPER ADMIN LOGIN\n";
echo str_repeat('=', 80) . "\n\n";

$testPasswords = ['password', 'admin123', 'superadmin', 'Admin123!'];

$admins = [
    ['email' => 'admin@ainstein.com', 'name' => 'Admin 1'],
    ['email' => 'superadmin@ainstein.com', 'name' => 'Admin 2'],
];

foreach ($admins as $adminInfo) {
    echo "📍 Testing: {$adminInfo['email']}\n";
    echo str_repeat('-', 80) . "\n";

    $user = App\Models\User::where('email', $adminInfo['email'])->first();

    if (!$user) {
        echo "❌ User not found\n\n";
        continue;
    }

    echo "✅ User found\n";
    echo "   ID: {$user->id}\n";
    echo "   Name: {$user->name}\n";
    echo "   Super Admin: " . ($user->is_super_admin ? 'Yes' : 'No') . "\n";
    echo "   Has password_hash: " . ($user->password_hash ? 'Yes' : 'No') . "\n\n";

    if (!$user->password_hash) {
        echo "⚠️  No password set! Setting password to 'admin123'...\n";
        $user->password_hash = Hash::make('admin123');
        $user->save();
        echo "✅ Password set to: admin123\n\n";
        continue;
    }

    echo "🔑 Testing passwords:\n";
    $found = false;
    foreach ($testPasswords as $pass) {
        if (Hash::check($pass, $user->password_hash)) {
            echo "   ✅ Password is: $pass\n";
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo "   ⚠️  Password not in test list. Resetting to 'admin123'...\n";
        $user->password_hash = Hash::make('admin123');
        $user->save();
        echo "   ✅ Password reset to: admin123\n";
    }

    echo "\n";
}

echo str_repeat('=', 80) . "\n";
echo "🎉 SUPER ADMIN ACCESS INFO\n";
echo str_repeat('=', 80) . "\n\n";

echo "🔐 Admin Panel Login:\n";
echo "   URL: http://127.0.0.1:8080/admin/login\n";
echo "   OR: http://127.0.0.1:8080/admin\n\n";

echo "👤 Admin Account 1:\n";
echo "   Email: admin@ainstein.com\n";
echo "   Password: [check above]\n\n";

echo "👤 Admin Account 2:\n";
echo "   Email: superadmin@ainstein.com\n";
echo "   Password: [check above]\n\n";

echo str_repeat('=', 80) . "\n";
