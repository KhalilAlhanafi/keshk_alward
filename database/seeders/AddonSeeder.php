<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addons = [
            [
                'name_ar' => 'علبة شوكولاتة',
                'price' => 25000,
                'is_active' => true,
            ],
            [
                'name_ar' => 'بالونات ملونة',
                'price' => 15000,
                'is_active' => true,
            ],
            [
                'name_ar' => 'كرت إهداء',
                'price' => 5000,
                'is_active' => true,
            ],
            [
                'name_ar' => 'غلاف فاخر',
                'price' => 12000,
                'is_active' => true,
            ],
        ];

        foreach ($addons as $addon) {
            Addon::create($addon);
        }
    }
}
