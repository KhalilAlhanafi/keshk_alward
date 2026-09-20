<?php

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function setupRequiredRoles(): void
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'store_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
}

function makeAdminUser(string $role = 'admin'): User
{
    setupRequiredRoles();
    $user = User::create([
        'name' => 'مدير النظام',
        'phone' => '+963911111111',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);
    return $user;
}

function makeCustomerUser(): User
{
    setupRequiredRoles();
    $user = User::create([
        'name' => 'عميل عادي',
        'phone' => '+963922222222',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('customer');
    return $user;
}

// ─── Authorization Tests ─────────────────────────────────────────────────────

test('customer is blocked from admin dashboard with 403', function () {
    $customer = makeCustomerUser();

    $this->actingAs($customer)
        ->getJson('/admin/dashboard')
        ->assertStatus(403);
});

test('admin can access dashboard with 200', function () {
    $admin = makeAdminUser('admin');

    $this->actingAs($admin)
        ->getJson('/admin/dashboard')
        ->assertStatus(200);
});

test('store manager can access dashboard with 200', function () {
    $manager = makeAdminUser('store_manager');

    $this->actingAs($manager)
        ->getJson('/admin/dashboard')
        ->assertStatus(200);
});

// ─── Dashboard Stats Tests ───────────────────────────────────────────────────

test('dashboard stats calculates counts and WoW percentage changes', function () {
    $admin = makeAdminUser('admin');

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'المزة',
        'delivery_fee' => 15000,
        'is_active' => true,
    ]);

    // Create past orders
    // Today
    Order::create([
        'order_number' => '#ORD-20260810-0001',
        'recipient_name' => 'سارة',
        'recipient_phone' => '+963944444444',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'الميدان',
        'delivery_date' => now()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'subtotal' => 100000,
        'delivery_fee' => 10000,
        'total' => 110000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::COD,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    // Traveling back to create old orders for YoY change
    $this->travelTo(now()->subDays(10));
    
    Order::create([
        'order_number' => '#ORD-20260801-0001',
        'recipient_name' => 'أحمد',
        'recipient_phone' => '+963944444445',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'المزة',
        'delivery_date' => now()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'subtotal' => 50000,
        'delivery_fee' => 10000,
        'total' => 60000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::COD,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $this->travelBack();

    $response = $this->actingAs($admin)->getJson('/admin/dashboard');

    $response->assertStatus(200)
        ->assertJsonPath('stats.orders.total', 2)
        ->assertJsonPath('stats.orders.change_percent', 0); // 1 order this week vs 1 order last week = 0% change
});

// ─── Product CRUD Tests ──────────────────────────────────────────────────────

test('admin can manage products CRUD', function () {
    $admin = makeAdminUser('admin');
    $category = Category::create(['name_ar' => 'باقات الورد', 'slug' => 'roses']);

    // 1. Create
    $response = $this->actingAs($admin)->postJson('/admin/products', [
        'category_id' => $category->id,
        'name_ar' => 'باقة حمراء',
        'description' => 'باقة ورد أحمر جوري',
        'sku' => 'PRD-RED-001',
        'base_price' => 85000,
        'is_best_seller' => true,
        'is_active' => true,
        'sizes' => [
            [
                'size_key' => 'medium',
                'label_ar' => 'وسط',
                'price' => 85000,
                'stock' => 15,
            ]
        ]
    ]);

    $response->assertStatus(210); // Custom 210 created status
    $productId = $response->json('product.id');

    // 2. Read
    $this->getJson("/admin/products/{$productId}")
        ->assertStatus(200)
        ->assertJsonPath('name_ar', 'باقة حمراء');

    // 3. Update
    $this->putJson("/admin/products/{$productId}", [
        'category_id' => $category->id,
        'name_ar' => 'باقة حمراء مميزة',
        'description' => 'باقة ورد أحمر جوري',
        'sku' => 'PRD-RED-001',
        'base_price' => 95000,
        'is_best_seller' => true,
        'is_active' => true,
        'sizes' => [
            [
                'size_key' => 'medium',
                'label_ar' => 'وسط',
                'price' => 95000,
                'stock' => 12,
            ]
        ]
    ])->assertStatus(200);

    // 4. Delete
    $this->deleteJson("/admin/products/{$productId}")
        ->assertStatus(200);

    expect(Product::find($productId))->toBeNull();
});

test('admin can upload product with image variant processing and long image_path', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $admin = makeAdminUser('admin');
    $category = Category::create(['name_ar' => 'شوكولا وهدايا', 'slug' => 'chocolates']);

    $image = \Illuminate\Http\UploadedFile::fake()->image('chocolate.jpg', 600, 600);

    $response = $this->actingAs($admin)->post('/admin/products', [
        'category_id' => $category->id,
        'name_ar' => 'شوكولا ميلكا',
        'description' => 'علبة خشب مع شوكولا',
        'sku' => 'KW-9904',
        'base_price' => 70000,
        'image' => $image,
        'is_best_seller' => false,
        'is_active' => true,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(210);
    $product = Product::where('sku', 'KW-9904')->first();
    expect($product)->not->toBeNull()
        ->and($product->image_path)->not->toBeNull()
        ->and($product->primary_image_url)->toContain('/storage/products/');
});

test('admin can upload multiple images, customize arrangement details, and omit sku', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $admin = makeAdminUser('admin');
    $category = Category::create(['name_ar' => 'باقات خاصة', 'slug' => 'special-bouquets']);

    $img1 = \Illuminate\Http\UploadedFile::fake()->image('angle1.jpg', 600, 600);
    $img2 = \Illuminate\Http\UploadedFile::fake()->image('angle2.png', 600, 600);

    // 1. Create with multiple images and arrangement details without explicit sku
    $response = $this->actingAs($admin)->post('/admin/products', [
        'category_id' => $category->id,
        'name_ar' => 'باقة الزنبق الملكي',
        'description' => 'باقة زنبق أبيض فاخر',
        'arrangement_details' => "10 زنابق بيضاء طبيعية\nتغليف كحلي مخملي\nشريط ذهبي مميز",
        'base_price' => 120000,
        'images' => [$img1, $img2],
        'is_best_seller' => true,
        'is_active' => true,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(210);
    $product = Product::where('name_ar', 'باقة الزنبق الملكي')->first();
    expect($product)->not->toBeNull()
        ->and($product->sku)->not->toBeEmpty()
        ->and(count($product->gallery_urls))->toBe(2)
        ->and($product->arrangement_points)->toHaveCount(3)
        ->and($product->arrangement_points[0])->toBe('10 زنابق بيضاء طبيعية');

    // 2. Update arrangement details and retain only 1 image
    $firstImageUrl = $product->gallery_urls[0];
    $updateResponse = $this->actingAs($admin)->post("/admin/products/{$product->id}", [
        '_method' => 'PUT',
        'category_id' => $category->id,
        'name_ar' => 'باقة الزنبق الملكي الفاخرة',
        'base_price' => 135000,
        'arrangement_details' => "12 زنبق أبيض\nتغليف ذهبي فاخر",
        'existing_images' => [$firstImageUrl],
    ], ['Accept' => 'application/json']);

    $updateResponse->assertStatus(200);
    $product->refresh();
    expect($product->name_ar)->toBe('باقة الزنبق الملكي الفاخرة')
        ->and($product->base_price)->toBe(135000)
        ->and(count($product->gallery_urls))->toBe(1)
        ->and($product->arrangement_points)->toHaveCount(2);
});

// ─── Order Transition Tests ──────────────────────────────────────────────────

test('admin can transition order status', function () {
    $admin = makeAdminUser('admin');

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق',
        'area_ar' => 'المزة',
        'delivery_fee' => 15000,
        'is_active' => true,
    ]);
    
    $order = Order::create([
        'order_number' => '#ORD-20260810-0005',
        'recipient_name' => 'نور',
        'recipient_phone' => '+963944444444',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'الشعلان',
        'delivery_date' => now()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'subtotal' => 120000,
        'delivery_fee' => 10000,
        'total' => 130000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::COD,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $response = $this->actingAs($admin)->patchJson("/admin/orders/{$order->id}/status", [
        'status' => 'processing',
    ]);

    $response->assertStatus(200);
    $order->refresh();
    expect($order->status)->toBe(OrderStatus::PROCESSING);
});

// ─── Settings Tests ──────────────────────────────────────────────────────────

test('admin can read and write settings', function () {
    $admin = makeAdminUser('admin');

    $this->actingAs($admin)->postJson('/admin/settings', [
        'sham_cash_wallet_code' => '0963933333333',
        'payment_cod_enabled' => true,
    ])->assertStatus(200);

    expect(Setting::get('sham_cash_wallet_code'))->toBe('0963933333333')
        ->and(Setting::get('payment_cod_enabled'))->toBe(true);

    $this->getJson('/admin/settings')
        ->assertStatus(200)
        ->assertJsonFragment(['sham_cash_wallet_code' => '0963933333333']);
});

// ─── Category CRUD Tests ─────────────────────────────────────────────────────

test('admin can manage categories CRUD and observe linked products count', function () {
    $admin = makeAdminUser('admin');

    // 1. Create Category
    $response = $this->actingAs($admin)->postJson('/admin/categories', [
        'name_ar' => 'زهور التوليب',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('category.name_ar', 'زهور التوليب');

    $categoryId = $response->json('category.id');

    // 2. Read Category
    $this->getJson("/admin/categories/{$categoryId}")
        ->assertStatus(200)
        ->assertJsonPath('name_ar', 'زهور التوليب');

    // 3. Update Category
    $this->putJson("/admin/categories/{$categoryId}", [
        'name_ar' => 'زهور التوليب الملكية',
        'sort_order' => 1,
        'is_active' => true,
    ])->assertStatus(200)
      ->assertJsonPath('category.name_ar', 'زهور التوليب الملكية');

    // 4. Delete Category
    $this->deleteJson("/admin/categories/{$categoryId}")
        ->assertStatus(200);

    expect(Category::find($categoryId))->toBeNull();
});

test('admin can upload category image with optimized webp base64 conversion', function () {
    $admin = makeAdminUser('admin');
    $image = \Illuminate\Http\UploadedFile::fake()->image('tulips.png', 800, 800);

    $response = $this->actingAs($admin)->post('/admin/categories', [
        'name_ar' => 'قسم الزهور مع صورة',
        'sort_order' => 1,
        'is_active' => true,
        'image' => $image,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(201);
    $category = Category::where('name_ar', 'قسم الزهور مع صورة')->first();
    expect($category)->not->toBeNull()
        ->and($category->image_path)->not->toBeNull()
        ->and($category->image_path)->toStartWith('categories/')
        ->and($category->image_url)->toContain('/storage/categories/');
});

// ─── Delivery Area CRUD Tests ────────────────────────────────────────────────

test('admin can manage delivery areas CRUD', function () {
    $admin = makeAdminUser('admin');

    // 1. Create Area
    $response = $this->actingAs($admin)->postJson('/admin/delivery-areas', [
        'city_ar' => 'دمشق',
        'area_ar' => 'المالكي',
        'delivery_fee' => 18000,
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('delivery_area.area_ar', 'المالكي');

    $areaId = $response->json('delivery_area.id');

    // 2. Read Area
    $this->getJson("/admin/delivery-areas/{$areaId}")
        ->assertStatus(200)
        ->assertJsonPath('area_ar', 'المالكي');

    // 3. Update Area
    $this->putJson("/admin/delivery-areas/{$areaId}", [
        'city_ar' => 'دمشق',
        'area_ar' => 'المالكي والروضة',
        'delivery_fee' => 20000,
        'is_active' => true,
    ])->assertStatus(200)
      ->assertJsonPath('delivery_area.delivery_fee', 20000);

    // 4. Delete Area
    $this->deleteJson("/admin/delivery-areas/{$areaId}")
        ->assertStatus(200);

    expect(DeliveryArea::find($areaId))->toBeNull();
});

// ─── Validation Enforcement Tests ────────────────────────────────────────────

test('validation rejects invalid product, category and delivery area submissions', function () {
    $admin = makeAdminUser('admin');

    // Product validation error (missing required fields, negative price)
    $this->actingAs($admin)->postJson('/admin/products', [
        'name_ar' => '',
        'base_price' => -500,
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['category_id', 'name_ar', 'base_price']);

    // Category validation error (empty name)
    $this->actingAs($admin)->postJson('/admin/categories', [
        'name_ar' => '',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['name_ar']);

    // Delivery area validation error (empty fields, negative fee)
    $this->actingAs($admin)->postJson('/admin/delivery-areas', [
        'city_ar' => '',
        'delivery_fee' => -100,
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['city_ar', 'area_ar', 'delivery_fee']);
});

