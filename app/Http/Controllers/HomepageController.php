<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Order;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\ImageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HomepageController extends Controller
{
    public function __invoke(ImageService $imageService): \Illuminate\View\View
    {
        // Fetch the latest active order for the logged-in user (non-delivered, non-cancelled)
        $latestActiveOrder = null;
        if (Auth::check()) {
            $latestActiveOrder = Order::where('user_id', Auth::id())
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->latest()
                ->first();
        }
        // Active hero slides — cached for 24 hours (busted by HeroSlide observer via CacheService)
        try {
            $heroSlides = Cache::remember(CacheService::KEY_HERO_SLIDES_ACTIVE, CacheService::TTL_HERO_SLIDES, function () {
                return HeroSlide::active()->get();
            });
        } catch (\Throwable $e) {
            Log::warning('HomepageController: Cache failed for hero_slides, falling back to DB.', ['error' => $e->getMessage()]);
            $heroSlides = HeroSlide::active()->get();
        }

        // Best sellers — cached for 1 hour (busted by Product observer via CacheService)
        try {
            $bestSellers = Cache::remember(CacheService::KEY_BEST_SELLERS, CacheService::TTL_BEST_SELLERS, function () {
                return Product::with(['category', 'sizes'])
                    ->where('is_active', true)
                    ->where('is_best_seller', true)
                    ->orderBy('created_at', 'desc')
                    ->limit(8)
                    ->get();
            });
        } catch (\Throwable $e) {
            Log::warning('HomepageController: Cache failed for best_sellers, falling back to DB.', ['error' => $e->getMessage()]);
            $bestSellers = Product::with(['category', 'sizes'])
                ->where('is_active', true)
                ->where('is_best_seller', true)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        }

        // Active parent categories — cached for 24 hours (busted by Category observer via CacheService)
        try {
            $categories = Cache::remember(CacheService::KEY_CATEGORIES_ACTIVE, CacheService::TTL_CATEGORIES, function () {
                return Category::with('children')
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            });
        } catch (\Throwable $e) {
            Log::warning('HomepageController: Cache failed for categories_active, falling back to DB.', ['error' => $e->getMessage()]);
            $categories = Category::with('children')
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        return view('homepage', compact('bestSellers', 'categories', 'heroSlides', 'latestActiveOrder'));
    }
}
