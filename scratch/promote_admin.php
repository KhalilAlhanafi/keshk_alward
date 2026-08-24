<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

$adminRole = Role::firstOrCreate(['name' => 'admin']);

// Assign admin role to all users or first user if none marked admin
$users = User::all();

foreach ($users as $user) {
    if ($user->role === 'admin' || $user->id === 1 || str_contains(strtolower($user->email ?? ''), 'admin')) {
        $user->role = 'admin';
        $user->save();
        $user->assignRole($adminRole);
        echo "User #{$user->id} ({$user->name} - {$user->phone}) is now an Admin.\n";
    }
}

echo "Admin promotion complete.\n";
