<?php

namespace Database\Seeders;

use App\Models\DeliveryArea;
use Illuminate\Database\Seeder;

class DeliveryAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['city_ar' => 'دمشق', 'area_ar' => 'المزة', 'delivery_fee' => 15000],
            ['city_ar' => 'دمشق', 'area_ar' => 'أبو رمانة', 'delivery_fee' => 12000],
            ['city_ar' => 'دمشق', 'area_ar' => 'المالكي', 'delivery_fee' => 12000],
            ['city_ar' => 'دمشق', 'area_ar' => 'الشعلان', 'delivery_fee' => 10000],
            ['city_ar' => 'دمشق', 'area_ar' => 'كفرسوسة', 'delivery_fee' => 15000],
            ['city_ar' => 'دمشق', 'area_ar' => 'الميدان', 'delivery_fee' => 10000],
            ['city_ar' => 'ريف دمشق', 'area_ar' => 'جرمانا', 'delivery_fee' => 20000],
            ['city_ar' => 'ريف دمشق', 'area_ar' => 'صحنايا', 'delivery_fee' => 25000],
        ];

        foreach ($areas as $area) {
            DeliveryArea::create([
                'city_ar' => $area['city_ar'],
                'area_ar' => $area['area_ar'],
                'delivery_fee' => $area['delivery_fee'],
                'is_active' => true,
            ]);
        }
    }
}
