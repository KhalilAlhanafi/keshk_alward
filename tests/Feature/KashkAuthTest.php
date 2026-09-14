<?php

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Enums\SizeKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed Spatie roles
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'store_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

test('user can register with a valid Syrian phone number and gets customer role', function () {
    $response = $this->post('/register', [
        'name' => 'صالح',
        'phone' => '+963988888888',
        'email' => 'saleh@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();

    $user = User::where('phone', '+963988888888')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('customer'))->toBeTrue();
    expect($user->role)->toBe('customer');
});

test('user cannot register with an invalid phone number format', function () {
    $response = $this->post('/register', [
        'name' => 'صالح',
        'phone' => '0988888888', // Invalid format, should start with +9639
        'email' => 'saleh2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('phone');
    $this->assertGuest();
});

test('user can login using phone number and redirects to home', function () {
    $user = User::create([
        'name' => 'علي',
        'phone' => '+963944444444',
        'email' => 'ali@example.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);
    $user->assignRole('customer');

    $response = $this->post('/login', [
        'login' => '+963944444444',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('user can login using email as fallback and redirects to home', function () {
    $user = User::create([
        'name' => 'علي',
        'phone' => '+963944444444',
        'email' => 'ali@example.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);
    $user->assignRole('customer');

    $response = $this->post('/login', [
        'login' => 'ali@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('authenticated user is redirected away from login and register to home page', function () {
    $user = User::create([
        'name' => 'مستخدم مسجل',
        'phone' => '+963955555555',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);
    $user->assignRole('customer');

    // Visiting login when authenticated redirects to home
    $loginResponse = $this->actingAs($user)->get('/login');
    $loginResponse->assertRedirect(route('home'));

    // Visiting register when authenticated redirects to home
    $registerResponse = $this->actingAs($user)->get('/register');
    $registerResponse->assertRedirect(route('home'));
});

test('auth pages deliver no-cache headers to prevent browser back-button caching', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
    $response->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
    $response->assertHeader('Pragma', 'no-cache');

    $regResponse = $this->get('/register');
    $regResponse->assertStatus(200);
    $regResponse->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
    $regResponse->assertHeader('Pragma', 'no-cache');
});

test('admin route group is guarded against guests and customers but allows admin/store_manager', function () {
    // 1. Guest
    $response = $this->get('/admin/dashboard');
    $response->assertRedirect('/login');

    // 2. Customer
    $customer = User::create([
        'name' => 'الزبون',
        'phone' => '+963911111111',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);
    $customer->assignRole('customer');

    $response = $this->actingAs($customer)->get('/admin/dashboard');
    $response->assertStatus(403); // Forbidden

    // 3. Store Manager
    $manager = User::create([
        'name' => 'مدير المحل',
        'phone' => '+963922222222',
        'password' => bcrypt('password'),
        'role' => 'store_manager',
    ]);
    $manager->assignRole('store_manager');

    $response = $this->actingAs($manager)->getJson('/admin/dashboard');
    $response->assertStatus(200)
             ->assertJsonStructure(['stats', 'recentProducts', 'recentOrders']);

    // 4. Admin
    $admin = User::create([
        'name' => 'المدير العام',
        'phone' => '+963933333333',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->getJson('/admin/dashboard');
    $response->assertStatus(200)
             ->assertJsonStructure(['stats', 'recentProducts', 'recentOrders']);
});

test('guest cart is automatically merged into user cart on login', function () {
    // Setup products & sizes
    $category = Category::create(['name_ar' => 'ورد', 'slug' => 'roses']);
    $product = Product::create([
        'category_id' => $category->id,
        'name_ar' => 'جوري',
        'slug' => 'jouri',
        'sku' => 'JR1',
        'base_price' => 10000,
    ]);
    $size = ProductSize::create([
        'product_id' => $product->id,
        'size_key' => SizeKey::MEDIUM,
        'label_ar' => 'وسط',
        'price' => 10000,
    ]);

    // Create guest cart
    $guestCart = Cart::create(['session_token' => 'guest-token-123']);
    $guestItem = CartItem::create([
        'cart_id' => $guestCart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 2,
    ]);

    // Create user with their own existing cart item
    $user = User::create([
        'name' => 'رائد',
        'phone' => '+963977777777',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('customer');

    $userCart = Cart::create(['user_id' => $user->id]);
    $userItem = CartItem::create([
        'cart_id' => $userCart->id,
        'product_id' => $product->id,
        'product_size_id' => $size->id,
        'quantity' => 3,
    ]);

    // Login with the session token cookie present
    $response = $this->withUnencryptedCookie('session_token', 'guest-token-123')
        ->post('/login', [
            'login' => '+963977777777',
            'password' => 'password',
        ]);

    $response->assertRedirect(route('home'));

    // Assert that the items merged (quantities summed: 2 + 3 = 5)
    $userItem->refresh();
    expect($userItem->quantity)->toBe(5);

    // Guest cart deleted
    expect(Cart::where('session_token', 'guest-token-123')->exists())->toBeFalse();
});
