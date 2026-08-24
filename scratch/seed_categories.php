<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use Illuminate\Support\Str;

$categoriesData = [
    [
        'name' => 'الباقات',
        'source_img' => 'C:\Users\User\.gemini\antigravity-ide\brain\e6eed89d-f0ae-4a50-87a9-c265ffd2b0ec\category_bouquets_1787399615995.jpg',
        'filename' => 'bouquets.jpg'
    ],
    [
        'name' => 'سلل الورد',
        'source_img' => 'C:\Users\User\.gemini\antigravity-ide\brain\e6eed89d-f0ae-4a50-87a9-c265ffd2b0ec\category_baskets_1787399665048.jpg',
        'filename' => 'baskets.jpg'
    ],
    [
        'name' => 'أعراس ومناسبات',
        'source_img' => 'C:\Users\User\.gemini\antigravity-ide\brain\e6eed89d-f0ae-4a50-87a9-c265ffd2b0ec\category_weddings_1787399716008.jpg',
        'filename' => 'weddings.jpg'
    ],
    [
        'name' => 'هدايا',
        'source_img' => 'C:\Users\User\.gemini\antigravity-ide\brain\e6eed89d-f0ae-4a50-87a9-c265ffd2b0ec\category_gifts_1787399775693.jpg',
        'filename' => 'gifts.jpg'
    ],
    [
        'name' => 'ديكورات و زينة',
        'source_img' => 'C:\Users\User\.gemini\antigravity-ide\brain\e6eed89d-f0ae-4a50-87a9-c265ffd2b0ec\category_decorations_1787399846715.jpg',
        'filename' => 'decorations.jpg'
    ],
    [
        'name' => 'شوكولا',
        'source_img' => 'C:\Users\User\.gemini\antigravity-ide\brain\e6eed89d-f0ae-4a50-87a9-c265ffd2b0ec\category_chocolate_1787399943949.jpg',
        'filename' => 'chocolate.jpg'
    ],
];

$targetDir = storage_path('app/public/categories');
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

foreach ($categoriesData as $data) {
    // Copy image
    $targetPath = $targetDir . '/' . $data['filename'];
    if (file_exists($data['source_img'])) {
        copy($data['source_img'], $targetPath);
    }
    
    // Create or update category
    $slug = Str::slug($data['name']);
    $category = Category::updateOrCreate(
        ['slug' => $slug],
        [
            'name_ar' => $data['name'],
            'image_path' => 'categories/' . $data['filename'],
            'is_active' => true,
        ]
    );
    
    echo "Category created/updated: {$category->name}\n";
}

echo "Done.\n";
