<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Ensure admin and store_manager roles exist
$adminRole = Role::firstOrCreate(['name' => 'admin']);
$managerRole = Role::firstOrCreate(['name' => 'store_manager']);
$customerRole = Role::firstOrCreate(['name' => 'customer']);

$users = User::all();

foreach ($users as $user) {
    if ($user->role === 'admin') {
        $user->assignRole($adminRole);
        echo "Assigned Spatie 'admin' role to User #{$user->id} ({$user->name})\n";
    } elseif ($user->role === 'store_manager') {
        $user->assignRole($managerRole);
        echo "Assigned Spatie 'store_manager' role to User #{$user->id} ({$user->name})\n";
    } else {
        $user->assignRole($customerRole);
    }
}

echo "Role sync completed successfully.\n";
