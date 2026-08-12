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

        // Create Admin
        $admin = User::firstOrCreate(
            ['phone' => '+963911111111'],
            [
                'name' => 'مدير النظام',
                'email' => 'admin@kashkalward.com',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create Store Manager
        $manager = User::firstOrCreate(
            ['phone' => '+963922222222'],
            [
                'name' => 'مدير الصالة',
                'email' => 'manager@kashkalward.com',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );
        $manager->assignRole('store_manager');
    }
}
