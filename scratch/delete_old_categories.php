<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;

$newCat = Category::where('name_ar', 'الباقات')->first()->id;

$oldCats = Category::whereNotIn('name_ar', ['الباقات', 'سلل الورد', 'أعراس ومناسبات', 'هدايا', 'ديكورات و زينة', 'شوكولا'])->pluck('id');

Product::whereIn('category_id', $oldCats)->update(['category_id' => $newCat]);

Category::whereIn('id', $oldCats)->delete();

echo "Old categories deleted and products reassigned to 'الباقات'.\n";
