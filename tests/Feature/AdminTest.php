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
