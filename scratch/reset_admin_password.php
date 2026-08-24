<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$adminRole = Role::firstOrCreate(['name' => 'admin']);

// Find or create admin account with +963911111111
$adminUser = User::where('phone', '+963911111111')
    ->orWhere('role', 'admin')
    ->first();

if (!$adminUser) {
    $adminUser = User::create([
        'name' => 'مدير النظام',
        'phone' => '+963911111111',
        'email' => 'admin@kashkward.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
    ]);
} else {
    $adminUser->phone = '+963911111111';
    $adminUser->role = 'admin';
    $adminUser->password = Hash::make('password');
    $adminUser->save();
}

$adminUser->assignRole($adminRole);

echo "Admin Account Ready:\n";
echo "ID: {$adminUser->id}\n";
echo "Name: {$adminUser->name}\n";
echo "Phone: {$adminUser->phone}\n";
echo "Email: {$adminUser->email}\n";
echo "Role: {$adminUser->role}\n";
echo "Password: password\n";
