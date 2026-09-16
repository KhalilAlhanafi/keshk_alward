<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'store_manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        // Create or update Admin
        $admin = User::firstOrNew(['phone' => '+963911111111']);
        $admin->name = 'مدير النظام';
        $admin->email = 'admin@kashkalward.com';
        $admin->password = bcrypt('password123');
        $admin->role = 'admin';
        $admin->email_verified_at = $admin->email_verified_at ?: now();
        $admin->save();
        $admin->assignRole('admin');
    }
}
