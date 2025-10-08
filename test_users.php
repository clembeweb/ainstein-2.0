<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

$users = App\Models\User::all(['email', 'name', 'role']);

echo "Found " . $users->count() . " users:\n";
foreach ($users as $user) {
    echo "- {$user->email} | {$user->name} | {$user->role}\n";
}