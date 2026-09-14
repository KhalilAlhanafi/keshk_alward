<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Addon;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Enums\SizeKey;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('helper format_money works correctly with Western Arabic numerals', function () {
    expect(format_money(125000))->toBe('125,000 ل.س.');
    expect(format_money(0))->toBe('0 ل.س.');
    expect(format_money(5000.5))->toBe('5,001 ل.س.');
});

test('can create categories and self-referencing relationship', function () {
    $parent = Category::create([
        'name_ar' => 'ورد جوري',
        'slug' => 'red-roses',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $child = Category::create([
        'name_ar' => 'جوري أحمر طويل',
        'slug' => 'long-red-roses',
        'parent_id' => $parent->id,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    expect($child->parent->id)->toBe($parent->id);
    expect($parent->children->first()->id)->toBe($child->id);
});

test('can create product and check relationships and accessors', function () {
    $category = Category::create([
        'name_ar' => 'باقات',
        'slug' => 'bouquets',
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'باقة الحب',
        'slug' => 'love-bouquet',
        'sku' => 'BQT-LOVE',
        'base_price' => 75000,
        'is_best_seller' => true,
        'is_active' => true,
    ]);

    expect($product->category->id)->toBe($category->id);
    expect($product->formatted_price)->toBe('75,000 ل.س.');
});

test('can create product size with size_key enum', function () {
    $category = Category::create(['name_ar' => 'ورد', 'slug' => 'roses']);
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'ورد فردي',
        'slug' => 'single-rose',
        'sku' => 'ROSE-SNG',
        'base_price' => 10000,
    ]);

    $size = ProductSize::create([
        'product_id' => $product->id,
        'size_key' => SizeKey::LARGE,
        'label_ar' => 'كبير',
        'price' => 15000,
        'stock' => 50,
    ]);

    expect($size->product->id)->toBe($product->id);
    expect($size->size_key)->toBe(SizeKey::LARGE);
    expect($size->size_key->labelAr())->toBe('كبير');
    expect($size->formatted_price)->toBe('15,000 ل.س.');
});

test('can create addon and product_addon pivot relations', function () {
    $category = Category::create(['name_ar' => 'ورد', 'slug' => 'roses']);
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'ورد فردي',
        'slug' => 'single-rose',
        'sku' => 'ROSE-SNG',
        'base_price' => 10000,
    ]);

    $addon = Addon::create([
        'name_ar' => 'كرت معايدة فاخر',
        'price' => 5000,
        'is_active' => true,
    ]);

    $product->addons()->attach($addon->id);

    expect($product->addons->first()->id)->toBe($addon->id);
    expect($addon->products->first()->id)->toBe($product->id);
    expect($addon->formatted_price)->toBe('5,000 ل.س.');
});

test('can create cart and cart_items relationships', function () {
    $user = User::create([
        'name' => 'محمد',
        'phone' => '+963911111111',
        'password' => bcrypt('password'),
    ]);

    $cart = Cart::create([
        'user_id' => $user->id,
    ]);

    $category = Category::create(['name_ar' => 'ورد', 'slug' => 'roses']);
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'ورد',
        'slug' => 'rose',
        'sku' => 'R1',
        'base_price' => 10000,
    ]);

    $size = ProductSize::create([
        'product_id' => $product->id,
        'size_key' => SizeKey::MEDIUM,
        'label_ar' => 'وسط',
        'price' => 10000,
        'stock' => 10,
    ]);

    $item = CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 2,
        'message' => 'عيد ميلاد سعيد',
    ]);

    expect($cart->items->first()->id)->toBe($item->id);
    expect($item->cart->id)->toBe($cart->id);
    expect($item->product->id)->toBe($product->id);
    expect($item->size->id)->toBe($size->id);
});

test('can create order, order_items, and payment_transactions with enums and accessors', function () {
    $user = User::create([
        'name' => 'أحمد',
        'phone' => '+963922222222',
        'password' => bcrypt('password'),
    ]);

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'المزة',
        'delivery_fee' => 15000,
    ]);

    $order = Order::create([
        'order_number' => '#ORD-001',
        'user_id' => $user->id,
        'recipient_name' => 'رنا',
        'recipient_phone' => '+963933333333',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'المزة أوتوستراد خلف الجلاء',
        'delivery_date' => now()->addDay()->toDateString(),
        'delivery_time_slot' => '10:00 - 12:00',
        'subtotal' => 120000,
        'delivery_fee' => $area->delivery_fee,
        'total' => 135000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::SHAM_CASH,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $category = Category::create(['name_ar' => 'ورد', 'slug' => 'roses']);
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'ورد',
        'slug' => 'rose',
        'sku' => 'R1',
        'base_price' => 10000,
    ]);

    $item = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name_snapshot' => 'باقة حمراء مع كرت',
        'size_label_snapshot' => 'وسط',
        'unit_price' => 60000,
        'quantity' => 2,
    ]);

    $pt = PaymentTransaction::create([
        'order_id' => $order->id,
        'gateway' => 'sham_cash',
        'reference_id' => 'TXN-999',
        'status' => 'success',
        'raw_payload' => ['amount' => 135000],
    ]);

    expect($order->user->id)->toBe($user->id);
    expect($order->deliveryArea->id)->toBe($area->id);
    expect($order->items->first()->id)->toBe($item->id);
    expect($order->transactions->first()->id)->toBe($pt->id);
    expect($order->status)->toBe(OrderStatus::PENDING);
    expect($order->payment_method)->toBe(PaymentMethod::SHAM_CASH);
    expect($order->payment_status)->toBe(PaymentStatus::PENDING);

    expect($order->formatted_total)->toBe('135,000 ل.س.');
    expect($order->formatted_subtotal)->toBe('120,000 ل.س.');
    expect($order->formatted_delivery_fee)->toBe('15,000 ل.س.');

    expect($item->formatted_price)->toBe('60,000 ل.س.');
    expect($item->formatted_subtotal)->toBe('120,000 ل.س.');

    expect($pt->order->id)->toBe($order->id);
    expect($pt->raw_payload)->toBeArray();
});

test('user can assign Spatie roles', function () {
    // Spatie roles setup
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    
    $user = User::create([
        'name' => 'المدير العام',
        'phone' => '+963955555555',
        'password' => bcrypt('password'),
    ]);

    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
});
