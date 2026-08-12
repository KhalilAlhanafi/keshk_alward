<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Enums\SizeKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $addons = Addon::all();

        if ($categories->isEmpty()) {
            return;
        }

        $productsData = [
            [
                'name_ar' => 'باقة جوري حمراء',
                'description' => 'باقة رائعة من الورد الجوري الأحمر، تعبر عن أسمى مشاعر الحب والرومانسية.',
                'base_price' => 75000,
                'is_best_seller' => true,
                'sizes' => [
                    ['size_key' => SizeKey::SMALL, 'label_ar' => 'صغير', 'price' => 50000, 'stock' => 20, 'is_default' => false],
                    ['size_key' => SizeKey::MEDIUM, 'label_ar' => 'وسط', 'price' => 75000, 'stock' => 15, 'is_default' => true],
                    ['size_key' => SizeKey::LARGE, 'label_ar' => 'كبير', 'price' => 110000, 'stock' => 5, 'is_default' => false],
                ],
            ],
            [
                'name_ar' => 'باقة التوليب الأبيض',
                'description' => 'باقة من التوليب الأبيض النقي، خيار مثالي للمناسبات الرسمية والأعراس.',
                'base_price' => 90000,
                'is_best_seller' => false,
                'sizes' => [
                    ['size_key' => SizeKey::MEDIUM, 'label_ar' => 'وسط', 'price' => 90000, 'stock' => 10, 'is_default' => true],
                    ['size_key' => SizeKey::LARGE, 'label_ar' => 'كبير', 'price' => 130000, 'stock' => 8, 'is_default' => false],
                ],
            ],
            [
                'name_ar' => 'تنسيقة الأوركيد الفاخرة',
                'description' => 'تنسيقة راقية من أزهار الأوركيد في فازة زجاجية أنيقة.',
                'base_price' => 150000,
                'is_best_seller' => true,
                'sizes' => [
                    ['size_key' => SizeKey::MEDIUM, 'label_ar' => 'قياسي', 'price' => 150000, 'stock' => 3, 'is_default' => true],
                ],
            ],
            [
                'name_ar' => 'باقة مكس ربيعي',
                'description' => 'مزيج مبهج من الأزهار الربيعية الملونة.',
                'base_price' => 60000,
                'is_best_seller' => false,
                'sizes' => [
                    ['size_key' => SizeKey::SMALL, 'label_ar' => 'صغير', 'price' => 45000, 'stock' => 25, 'is_default' => false],
                    ['size_key' => SizeKey::MEDIUM, 'label_ar' => 'وسط', 'price' => 60000, 'stock' => 20, 'is_default' => true],
                ],
            ],
            [
                'name_ar' => 'صندوق السعادة',
                'description' => 'صندوق خشبي أنيق يحتوي على تشكيلة من الورود مع مساحة مخصصة للهدية.',
                'base_price' => 120000,
                'is_best_seller' => true,
                'sizes' => [
                    ['size_key' => SizeKey::MEDIUM, 'label_ar' => 'قياسي', 'price' => 120000, 'stock' => 12, 'is_default' => true],
                ],
            ],
        ];

        foreach ($productsData as $index => $data) {
            // Pick a random category
            $category = $categories->random();

            $product = Product::create([
                'category_id' => $category->id,
                'name_ar' => $data['name_ar'],
                'slug' => Str::slug($data['name_ar'], '-', 'ar') ?: 'prod-' . ($index + 1),
                'description' => $data['description'],
                'sku' => 'PRD-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'base_price' => $data['base_price'],
                'is_best_seller' => $data['is_best_seller'],
                'is_active' => true,
            ]);

            foreach ($data['sizes'] as $sizeData) {
                ProductSize::create([
                    'product_id' => $product->id,
                    'size_key' => $sizeData['size_key'],
                    'label_ar' => $sizeData['label_ar'],
                    'price' => $sizeData['price'],
                    'stock' => $sizeData['stock'],
                    'is_default' => $sizeData['is_default'],
                ]);
            }

            // Attach 1 to 3 random addons to each product
            if ($addons->isNotEmpty()) {
                $randomAddons = $addons->random(rand(1, min(3, $addons->count())))->pluck('id');
                $product->addons()->attach($randomAddons);
            }
        }
    }
}
