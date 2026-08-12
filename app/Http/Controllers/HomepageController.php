<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\ImageService;
use Illuminate\Support\Facades\Cache;

class HomepageController extends Controller
{
    public function __invoke(ImageService $imageService): \Illuminate\View\View
    {
        // Best sellers — cached for 1 hour (busted by Product observer via CacheService)
        $bestSellers = Cache::remember(CacheService::KEY_BEST_SELLERS, CacheService::TTL_BEST_SELLERS, function () {
            return Product::with(['category', 'sizes'])
                ->where('is_active', true)
                ->where('is_best_seller', true)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        });

        // Active parent categories — cached for 24 hours (busted by Category observer via CacheService)
        $categories = Cache::remember(CacheService::KEY_CATEGORIES_ACTIVE, CacheService::TTL_CATEGORIES, function () {
            return Category::with('children')
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        return view('homepage', compact('bestSellers', 'categories'));
    }
}
