<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Setting;
use App\Models\User;
use App\Services\CacheService;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** @var TestCase $this */

uses(RefreshDatabase::class);

// ─── CacheService key builder ──────────────────────────────────────────────

test('CacheService.catalogKey produces identical keys regardless of param order', function () {
    $params1 = ['sort_by' => 'newest', 'category_id' => '3', 'page' => '2'];
    $params2 = ['category_id' => '3', 'page' => '2', 'sort_by' => 'newest'];

    expect(CacheService::catalogKey($params1))->toBe(CacheService::catalogKey($params2));
});

test('CacheService.catalogKey produces different keys for different params', function () {
    $keyA = CacheService::catalogKey(['sort_by' => 'newest', 'page' => '1']);
    $keyB = CacheService::catalogKey(['sort_by' => 'price_asc', 'page' => '1']);

    expect($keyA)->not->toBe($keyB);
});

test('CacheService.catalogKey always starts with catalog:', function () {
    $key = CacheService::catalogKey(['sort_by' => 'newest']);
    expect($key)->toStartWith('catalog:');
});

// ─── TTL constants ─────────────────────────────────────────────────────────

test('CacheService TTL constants have correct values', function () {
    expect(CacheService::TTL_CATALOG)->toBe(15 * 60)
        ->and(CacheService::TTL_BEST_SELLERS)->toBe(60 * 60)
        ->and(CacheService::TTL_CATEGORIES)->toBe(24 * 60 * 60)
        ->and(CacheService::TTL_DASHBOARD)->toBe(5 * 60)
        ->and(CacheService::TTL_SETTINGS)->toBe(60 * 60);
});

// ─── Catalog caching via CatalogController ─────────────────────────────────

test('CatalogController caches results and returns consistent data on repeated calls', function () {
    $category = Category::create(['name_ar' => 'ورد', 'slug' => 'ward-' . uniqid(), 'is_active' => true]);
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'باقة جوري',
        'slug' => 'bouquet-' . uniqid(),
        'sku' => 'SKU-' . uniqid(),
        'base_price' => 75000,
        'is_active' => true,
    ]);

    // First request — hydrates the cache
    $response1 = $this->getJson('/catalog');
    $response1->assertStatus(200);
    $total1 = $response1->json('pagination.total');

    // Second request — must come from cache (same result)
    $response2 = $this->getJson('/catalog');
    $response2->assertStatus(200);
    $total2 = $response2->json('pagination.total');

    expect($total1)->toBe($total2)->toBe(1);
});

// ─── Observer cache invalidation ───────────────────────────────────────────

test('Product::save busts best_sellers cache', function () {
    // Seed the cache manually
    Cache::put(CacheService::KEY_BEST_SELLERS, 'STALE_DATA', 3600);

    $category = Category::create(['name_ar' => 'ورود', 'slug' => 'roses-' . uniqid(), 'is_active' => true]);
    Product::create([
        'category_id' => $category->id,
        'name_ar' => 'باقة جديدة',
        'slug' => 'new-bouquet-' . uniqid(),
        'sku' => 'NEW-' . uniqid(),
        'base_price' => 50000,
        'is_active' => true,
    ]);

    // Cache must be gone after the product was created
    expect(Cache::has(CacheService::KEY_BEST_SELLERS))->toBeFalse();
});

test('Product::save busts admin_dashboard_stats cache', function () {
    Cache::put(CacheService::KEY_DASHBOARD_STATS, 'STALE_STATS', 300);

    $category = Category::create(['name_ar' => 'ورود', 'slug' => 'r-' . uniqid(), 'is_active' => true]);
    Product::create([
        'category_id' => $category->id,
        'name_ar' => 'منتج جديد',
        'slug' => 'prd-' . uniqid(),
        'sku' => 'P-' . uniqid(),
        'base_price' => 40000,
        'is_active' => true,
    ]);

    expect(Cache::has(CacheService::KEY_DASHBOARD_STATS))->toBeFalse();
});

test('Category::save busts categories_active cache', function () {
    Cache::put(CacheService::KEY_CATEGORIES_ACTIVE, 'STALE_CATEGORIES', 86400);

    Category::create(['name_ar' => 'تصنيف جديد', 'slug' => 'new-cat-' . uniqid()]);

    expect(Cache::has(CacheService::KEY_CATEGORIES_ACTIVE))->toBeFalse();
});

test('Category::delete busts categories_all cache', function () {
    Cache::put(CacheService::KEY_CATEGORIES_ALL, 'STALE_ALL', 86400);

    $cat = Category::create(['name_ar' => 'مؤقت', 'slug' => 'tmp-' . uniqid()]);
    $cat->delete();

    expect(Cache::has(CacheService::KEY_CATEGORIES_ALL))->toBeFalse();
});

test('Order::save busts admin_dashboard_stats cache', function () {
    Cache::put(CacheService::KEY_DASHBOARD_STATS, 'STALE_DASHBOARD', 300);

    $area = \App\Models\DeliveryArea::create([
        'city_ar' => 'دمشق', 'area_ar' => 'المزة',
        'delivery_fee' => 10000, 'is_active' => true,
    ]);

    Order::create([
        'order_number' => '#ORD-TEST-0001',
        'recipient_name' => 'أحمد',
        'recipient_phone' => '+963911111111',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'شارع الثورة',
        'delivery_date' => now()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'subtotal' => 50000,
        'delivery_fee' => 10000,
        'total' => 60000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::COD,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    expect(Cache::has(CacheService::KEY_DASHBOARD_STATS))->toBeFalse();
});

// ─── Settings cache ────────────────────────────────────────────────────────

test('Setting::get caches values after first call', function () {
    Setting::set('delivery_banner_text', 'توصيل مجاني اليوم!');

    // At this point cache is busted by the observer; hydrate it by calling get()
    Setting::get('delivery_banner_text');

    // Now the cache should be populated
    expect(Cache::has(CacheService::KEY_SETTINGS_ALL))->toBeTrue();
});

test('Setting::set busts the settings cache', function () {
    // Seed cache
    Cache::put(CacheService::KEY_SETTINGS_ALL, 'STALE_SETTINGS', 3600);

    // Calling set() should bust it
    Setting::set('sham_cash_wallet_code', '0963900000000');

    expect(Cache::has(CacheService::KEY_SETTINGS_ALL))->toBeFalse();
});

test('Setting::get returns correct value from cache on second read', function () {
    Setting::set('hero_title', 'مرحباً بكم في كشك الورد');

    // First read — hydrates cache
    $val1 = Setting::get('hero_title');
    // Second read — from cache
    $val2 = Setting::get('hero_title');

    expect($val1)->toBe('مرحباً بكم في كشك الورد')
        ->and($val2)->toBe($val1);
});

test('Setting::get returns default when key does not exist', function () {
    $val = Setting::get('non_existent_key', 'قيمة افتراضية');
    expect($val)->toBe('قيمة افتراضية');
});
