<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemAddon;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Addon;
use App\Models\User;
use App\Models\Setting;
use App\Enums\SizeKey;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\CartTotalsService;
use App\Services\OrderService;
use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'store_manager', 'guard_name' => 'web']);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeProductWithSize(string $slug = 'rose', int $price = 50000, int $stock = 10): array
{
    $category = Category::create(['name_ar' => 'ورد', 'slug' => $slug . '-cat-' . uniqid()]);
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'ورد فردي',
        'slug' => $slug . '-' . uniqid(),
        'sku' => 'R-' . uniqid(),
        'base_price' => $price,
        'is_active' => true,
    ]);
    $size = ProductSize::create([
        'product_id' => $product->id,
        'size_key' => SizeKey::MEDIUM,
        'label_ar' => 'وسط',
        'price' => $price,
        'stock' => $stock,
    ]);
    return [$product, $size];
}

function makeUser(string $phone = '+963911111111'): User
{
    $user = User::create([
        'name' => 'مستخدم',
        'phone' => $phone,
        'password' => bcrypt('password'),
    ]);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $user->assignRole('customer');
    return $user;
}

// ─── CartTotalsService Tests ─────────────────────────────────────────────────

test('CartTotalsService calculates correct totals including addons', function () {
    [$product, $size] = makeProductWithSize('rose1', 50000);

    $addon = Addon::create([
        'name_ar' => 'شوكولاتة فاخرة',
        'price' => 25000,
        'is_active' => true,
    ]);

    $user = makeUser('+963911111111');
    $cart = Cart::create(['user_id' => $user->id]);
    $item = CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 3,
    ]);

    CartItemAddon::create([
        'cart_item_id' => $item->id,
        'addon_id' => $addon->id,
        'price_snapshot' => $addon->price,
    ]);

    $service = app(CartTotalsService::class);
    $totals = $service->calculate($cart, 15000);

    // subtotal = 50000 * 3 = 150,000
    // addons_total = 25000 * 3 = 75,000
    // delivery_fee = 15,000
    // grand_total = 150,000 + 75,000 + 15,000 = 240,000
    expect($totals['subtotal'])->toBe(150000)
        ->and($totals['addons_total'])->toBe(75000)
        ->and($totals['delivery_fee'])->toBe(15000)
        ->and($totals['total'])->toBe(240000)
        ->and($totals['formatted_subtotal'])->toBe('150,000 ل.س.')
        ->and($totals['formatted_addons_total'])->toBe('75,000 ل.س.')
        ->and($totals['formatted_delivery_fee'])->toBe('15,000 ل.س.')
        ->and($totals['formatted_total'])->toBe('240,000 ل.س.');
});

// ─── Cart API Tests ──────────────────────────────────────────────────────────

test('authenticated user can add item to cart', function () {
    [$product, $size] = makeProductWithSize('rose2', 60000);

    $user = makeUser('+963977111111');

    $response = $this->actingAs($user)
        ->postJson('/cart', [
            'product_id' => $product->id,
            'product_size_id' => $size->id,
            'quantity' => 1,
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('items.0.product_id', $product->id);
    $response->assertJsonPath('items.0.quantity', 1);
});

test('user can add item to cart with selected wrapping color and place order', function () {
    [$product, $size] = makeProductWithSize('rose-gold-wrap', 75000);
    $user = makeUser('+963977888888');

    $response = $this->actingAs($user)
        ->postJson('/cart', [
            'product_id' => $product->id,
            'product_size_id' => $size->id,
            'wrapping_color' => 'ذهبي فاخر',
            'quantity' => 2,
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('items.0.wrapping_color', 'ذهبي فاخر');

    $cart = Cart::where('user_id', $user->id)->first();
    expect($cart->items->first()->wrapping_color)->toBe('ذهبي فاخر');

    // Place order and ensure wrapping_color is snapshotted on order_items
    $deliveryArea = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'المزة',
        'delivery_fee' => 15000,
        'is_active' => true,
    ]);

    $orderService = app(\App\Services\OrderService::class);
    $order = $orderService->placeOrder($cart, [
        'delivery_area_id' => $deliveryArea->id,
        'recipient_name' => 'محمد أحمد',
        'recipient_phone' => '+963977888888',
        'delivery_address' => 'المزة - فيلات غربية',
        'delivery_date' => now()->addDays(1)->format('Y-m-d'),
        'delivery_time_slot' => '10:00 - 14:00',
        'payment_method' => 'cod',
    ]);

    expect($order->items->first()->wrapping_color)->toBe('ذهبي فاخر');
});

// ─── Wishlist API Tests ───────────────────────────────────────────────────────

test('authenticated user can toggle wishlist', function () {
    [$product, $size] = makeProductWithSize('rose5', 20000);

    $user = makeUser('+963977222222');

    // Add to wishlist
    $addResponse = $this->actingAs($user)
        ->postJson('/wishlist/toggle', ['product_id' => $product->id]);

    $addResponse->assertStatus(200);
    $addResponse->assertJsonPath('status', 'added');
    $addResponse->assertJsonPath('count', 1);

    // Toggle again — should remove
    $removeResponse = $this->actingAs($user)
        ->postJson('/wishlist/toggle', ['product_id' => $product->id]);

    $removeResponse->assertStatus(200);
    $removeResponse->assertJsonPath('status', 'removed');
    $removeResponse->assertJsonPath('count', 0);
});

test('profile page displays wishlisted products from database', function () {
    [$product, $size] = makeProductWithSize('orchid-wish', 65000);
    $user = makeUser('+963977333333');

    \App\Models\Wishlist::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($user)->get('/profile');
    $response->assertStatus(200);
    $response->assertSee($product->name_ar);
});

// ─── OrderService & Checkout Tests ───────────────────────────────────────────

test('OrderService places order, snapshots addons, and decrements stock', function () {
    Event::fake();

    [$product, $size] = makeProductWithSize('rose6', 80000, 10);

    $addon = Addon::create([
        'name_ar' => 'شوكولاتة فاخرة',
        'price' => 25000,
        'is_active' => true,
    ]);

    $user = makeUser('+963933333333');
    $cart = Cart::create(['user_id' => $user->id]);
    $item = CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 2,
    ]);

    CartItemAddon::create([
        'cart_item_id' => $item->id,
        'addon_id' => $addon->id,
        'price_snapshot' => $addon->price,
    ]);

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'المزة',
        'delivery_fee' => 15000,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $orderService = app(OrderService::class);
    $order = $orderService->placeOrder($cart, [
        'recipient_name' => 'سارة',
        'recipient_phone' => '+963944444444',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'الميدان شارع الثورة',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'payment_method' => 'cod',
    ]);

    expect($order->order_number)->toStartWith('#ORD-')
        ->and($order->subtotal)->toBe(160000)   // 80000 x 2
        ->and($order->delivery_fee)->toBe(15000)
        ->and($order->total)->toBe(225000)       // 160000 + 50000 + 15000
        ->and($order->status)->toBe(OrderStatus::PENDING);

    // Check stock decremented
    $size->refresh();
    expect($size->stock)->toBe(8); // 10 - 2

    // Check addon snapshotted
    $orderItem = $order->items->first();
    expect($orderItem->addons)->toHaveCount(1)
        ->and($orderItem->addons->first()->addon_name_snapshot)->toBe('شوكولاتة فاخرة')
        ->and($orderItem->addons->first()->price_snapshot)->toBe(25000);

    // Event dispatched
    Event::assertDispatched(OrderPlaced::class);
});

test('same-day delivery cutoff validation works at 21:00 (9 PM)', function () {
    $user = makeUser('+963988888888');
    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'الميدان',
        'delivery_fee' => 10000,
        'is_active' => true,
    ]);

    // 1. Travel to Damascus 20:00 (8:00 PM) - before 21:00 cutoff: order should succeed
    $this->travelTo(now()->setTimezone('Asia/Damascus')->setTime(20, 0, 0));
    $cart = Cart::create(['user_id' => $user->id]);
    [$product, $size] = makeProductWithSize('flower_late', 50000);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 1,
    ]);

    $validResponse = $this->actingAs($user)->postJson('/orders', [
        'recipient_name' => 'أحمد',
        'recipient_phone' => '+963966666666',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'العدوي',
        'delivery_date' => now()->setTimezone('Asia/Damascus')->format('Y-m-d'), // Today
        'delivery_time_slot' => '21:00 - 23:00',
        'payment_method' => 'cod',
    ]);
    $validResponse->assertStatus(200);

    // 2. Travel to Damascus 21:30 (9:30 PM) - past 21:00 cutoff: order for same day should be rejected
    $this->travelTo(now()->setTimezone('Asia/Damascus')->setTime(21, 30, 0));
    $invalidResponse = $this->actingAs($user)->postJson('/orders', [
        'recipient_name' => 'أحمد',
        'recipient_phone' => '+963966666666',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'العدوي',
        'delivery_date' => now()->setTimezone('Asia/Damascus')->format('Y-m-d'), // Today
        'delivery_time_slot' => '21:00 - 23:00',
        'payment_method' => 'cod',
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors('delivery_date');
});

test('Sham Cash order retrieval displays wallet code from setting', function () {
    Setting::set('sham_cash_wallet_code', '0963999999999');

    [$product, $size] = makeProductWithSize('rose7', 10000);
    $user = makeUser('+963922222229');
    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 1,
    ]);

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'المهاجرين',
        'delivery_fee' => 5000,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->postJson('/orders', [
        'recipient_name' => 'ماهر',
        'recipient_phone' => '+963944444444',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'شارع الجلاء',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'payment_method' => 'sham_cash',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('sham_cash_wallet_code', '0963999999999');
});

// ─── DeliveryArea API Tests ──────────────────────────────────────────────────

test('DeliveryArea API returns unique cities list', function () {
    DeliveryArea::create(['city_ar' => 'دمشق', 'area_ar' => 'أبوقبان', 'delivery_fee' => 10000, 'is_active' => true]);
    DeliveryArea::create(['city_ar' => 'دمشق', 'area_ar' => 'القصاع', 'delivery_fee' => 10000, 'is_active' => true]);
    DeliveryArea::create(['city_ar' => 'حلب', 'area_ar' => 'الشهباء', 'delivery_fee' => 20000, 'is_active' => true]);

    $response = $this->getJson('/api/delivery-areas');

    $response->assertStatus(200);
    expect($response->json())->toContain('دمشق')->toContain('حلب');
});

test('DeliveryArea API returns areas filtered by city', function () {
    DeliveryArea::create(['city_ar' => 'دمشق', 'area_ar' => 'أبوقبان', 'delivery_fee' => 12000, 'is_active' => true]);
    DeliveryArea::create(['city_ar' => 'حلب', 'area_ar' => 'الشهباء', 'delivery_fee' => 20000, 'is_active' => true]);

    $response = $this->getJson('/api/delivery-areas?city=دمشق');

    $response->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.area', 'أبوقبان')
        ->assertJsonPath('0.fee', 12000);
});

// ─── Product API Tests ───────────────────────────────────────────────────────

test('Product show API returns product with relations', function () {
    [$product, $size] = makeProductWithSize('lily', 35000);

    $response = $this->getJson("/products/{$product->slug}");

    $response->assertStatus(200)
        ->assertJsonPath('product.slug', $product->slug)
        ->assertJsonPath('product.sizes.0.id', $size->id);
});

test('Product price-preview API computes live price with size and addons', function () {
    [$product, $size] = makeProductWithSize('tulip', 40000);

    $addon = Addon::create([
        'name_ar' => 'كرت إهداء',
        'price' => 5000,
        'is_active' => true,
    ]);
    
    // Attach addon to product
    $product->addons()->attach($addon->id);

    // Default base price
    $response1 = $this->getJson("/products/{$product->id}/price-preview");
    $response1->assertStatus(200)
        ->assertJsonPath('price', 40000)
        ->assertJsonPath('formatted_price', '40,000 ل.س.');

    // With size (assuming size price is same as base for makeProductWithSize)
    $response2 = $this->getJson("/products/{$product->id}/price-preview?size_id={$size->id}");
    $response2->assertStatus(200)
        ->assertJsonPath('price', 40000);

    // With size and addon
    $response3 = $this->getJson("/products/{$product->id}/price-preview?size_id={$size->id}&addons[]={$addon->id}");
    $response3->assertStatus(200)
        ->assertJsonPath('price', 45000)
        ->assertJsonPath('formatted_price', '45,000 ل.س.');
});

// ─── Catalog Filtering, Sorting & Pagination Tests ──────────────────────────

test('Catalog listing can be filtered and sorted correctly', function () {
    // Flush cache first to avoid interference
    \Illuminate\Support\Facades\Cache::flush();

    // Clean database first for reliable catalog listing results
    Category::query()->delete();
    Product::query()->delete();

    $cat1 = Category::create(['name_ar' => 'باقات حمراء', 'slug' => 'red-roses']);
    $cat2 = Category::create(['name_ar' => 'باقات بيضاء', 'slug' => 'white-roses']);

    // Product 1: 50,000 SYP, Cat 1, Oldest
    $p1 = new Product([
        'category_id' => $cat1->id,
        'name_ar' => 'ورد جوري أحمر',
        'slug' => 'red-jouri',
        'sku' => 'PRD-1',
        'base_price' => 50000,
        'is_active' => true,
    ]);
    $p1->timestamps = false;
    $p1->created_at = now()->subDays(3);
    $p1->save();

    // Product 2: 90,000 SYP, Cat 2, Middle
    $p2 = new Product([
        'category_id' => $cat2->id,
        'name_ar' => 'توليب أبيض',
        'slug' => 'white-tulip',
        'sku' => 'PRD-2',
        'base_price' => 90000,
        'is_active' => true,
    ]);
    $p2->timestamps = false;
    $p2->created_at = now()->subDays(2);
    $p2->save();

    // Product 3: 150,000 SYP, Cat 1, Newest
    $p3 = new Product([
        'category_id' => $cat1->id,
        'name_ar' => 'أوركيد أحمر فاخر',
        'slug' => 'red-orchid',
        'sku' => 'PRD-3',
        'base_price' => 150000,
        'is_active' => true,
    ]);
    $p3->timestamps = false;
    $p3->created_at = now()->subDays(1);
    $p3->save();

    // 1. Filter by category
    $response = $this->getJson("/catalog?category_id={$cat1->id}");
    $response->assertStatus(200)
        ->assertJsonCount(2, 'products')
        ->assertJsonPath('products.0.id', $p3->id) // Default sort newest
        ->assertJsonPath('products.1.id', $p1->id);

    // 2. Filter by price range (min_price=60000, max_price=100000)
    $response = $this->getJson('/catalog?min_price=60000&max_price=100000');
    $response->assertStatus(200)
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.id', $p2->id);

    // 3. Sort by price asc
    $response = $this->getJson('/catalog?sort_by=price_asc');
    $response->assertStatus(200)
        ->assertJsonPath('products.0.id', $p1->id)
        ->assertJsonPath('products.1.id', $p2->id)
        ->assertJsonPath('products.2.id', $p3->id);

    // 4. Sort by price desc
    $response = $this->getJson('/catalog?sort_by=price_desc');
    $response->assertStatus(200)
        ->assertJsonPath('products.0.id', $p3->id)
        ->assertJsonPath('products.1.id', $p2->id)
        ->assertJsonPath('products.2.id', $p1->id);
});
