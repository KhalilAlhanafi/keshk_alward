<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Enums\SizeKey;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'store_manager', 'guard_name' => 'web']);
});

test('authenticated user can checkout with cash on delivery', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+963912345678',
        'password' => bcrypt('password'),
    ]);
    
    $category = Category::create([
        'name_ar' => 'Test Cat',
        'slug' => 'test-cat-' . uniqid(),
    ]);
    
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'Test Product',
        'slug' => 'test-product-' . uniqid(),
        'sku' => 'SKU-' . uniqid(),
        'base_price' => 15000,
        'is_active' => true,
    ]);
    
    $size = ProductSize::create([
        'product_id' => $product->id,
        'size_key' => SizeKey::MEDIUM,
        'label_ar' => 'وسط',
        'price' => 15000,
        'stock' => 10,
    ]);

    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 2,
    ]);

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'المزة',
        'delivery_fee' => 5000,
        'is_active' => true,
    ]);

    $response = actingAs($user)->postJson('/orders', [
        'recipient_name' => 'Test User',
        'recipient_phone' => '+963912345678',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'Damascus, Test Street',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'delivery_time_slot' => '10:00',
        'payment_method' => PaymentMethod::COD->value,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['message' => 'تم إتمام طلبك بنجاح!']);

    assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'payment_method' => PaymentMethod::COD->value,
        'payment_status' => PaymentStatus::PENDING->value,
    ]);

    assertDatabaseMissing('carts', ['id' => $cart->id]);
});

test('user can checkout with sham cash and status is awaiting verification', function () {
    $user = User::create([
        'name' => 'Sham User',
        'email' => 'sham@example.com',
        'phone' => '+963999999999',
        'password' => bcrypt('password'),
    ]);
    
    $category = Category::create([
        'name_ar' => 'Test Cat 2',
        'slug' => 'test-cat-' . uniqid(),
    ]);
    
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'Test Product 2',
        'slug' => 'test-product-' . uniqid(),
        'sku' => 'SKU-' . uniqid(),
        'base_price' => 20000,
        'is_active' => true,
    ]);
    
    $size = ProductSize::create([
        'product_id' => $product->id,
        'size_key' => SizeKey::LARGE,
        'label_ar' => 'كبير',
        'price' => 20000,
        'stock' => 5,
    ]);

    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 1,
    ]);

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'الميدان',
        'delivery_fee' => 3000,
        'is_active' => true,
    ]);

    $response = actingAs($user)->postJson('/orders', [
        'recipient_name' => 'Sham Cash User',
        'recipient_phone' => '+963999999999',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'Sham Cash Address',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'delivery_time_slot' => '14:00',
        'payment_method' => PaymentMethod::SHAM_CASH->value,
    ]);

    $response->assertStatus(200);

    assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'payment_method' => PaymentMethod::SHAM_CASH->value,
        'payment_status' => PaymentStatus::AWAITING_VERIFICATION->value,
    ]);
});
