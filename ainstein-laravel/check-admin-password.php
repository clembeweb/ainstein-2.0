<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "\n🔐 Verifica Password Super Admin\n\n";

$admin = User::where('email', 'admin@ainstein.com')->first();

if (!$admin) {
    echo "❌ Super admin non trovato!\n";
    exit(1);
}

echo "✅ Super Admin trovato:\n";
echo "   Email: {$admin->email}\n";
echo "   Nome: {$admin->name}\n";
echo "   Is Super Admin: " . ($admin->is_super_admin ? 'Sì' : 'No') . "\n";
echo "   Is Active: " . ($admin->is_active ? 'Sì' : 'No') . "\n";

// Test password
$testPasswords = ['admin123', 'Admin123!', 'password'];

echo "\n🧪 Test password:\n";
foreach ($testPasswords as $pwd) {
    $check = Hash::check($pwd, $admin->password_hash);
    echo "   - '{$pwd}': " . ($check ? '✅ CORRETTA' : '❌ Errata') . "\n";
    if ($check) {
        echo "\n✨ Password corretta: {$pwd}\n\n";
        exit(0);
    }
}

echo "\n⚠️  Nessuna password testata funziona. Reset password...\n";

// Reset password
$admin->password_hash = Hash::make('admin123');
$admin->save();

echo "✅ Password resettata a: admin123\n\n";
