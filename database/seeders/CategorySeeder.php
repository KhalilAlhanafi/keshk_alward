<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'مناسبات',
            'باقات الورد',
            'شجر زينة',
            'نباتات',
            'شيكولاتة',
            'هدايا',
        ];

        foreach ($categories as $index => $name) {
            Category::create([
                'name_ar' => $name,
                'slug' => Str::slug($name, '-', 'ar') ?: 'cat-' . ($index + 1),
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
