<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// ───────────────────────────────────────────────────────────
//  Public Routes
// ───────────────────────────────────────────────────────────

// Phase 1 Test Route & Styleguide
Route::get('/phase1-test', function () {
    return view('layouts.test');
})->name('phase1.test');

Route::get('/dev/styleguide', function () {
    return view('components.dev');
})->name('dev.styleguide');

// Phase 2 Components Demo Route
Route::get('/phase2-components', function () {
    return view('components.dev');
})->name('phase2.components');

Route::get('/', HomepageController::class)->name('home');

// Catalog & Products
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('products.show');
Route::get('/products/{id}/price-preview', [CatalogController::class, 'pricePreview'])->name('products.price-preview');

// ───────────────────────────────────────────────────────────
//  Cart — available for guests and authenticated users
// ───────────────────────────────────────────────────────────

Route::prefix('cart')->name('cart.')->middleware('throttle:public')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::put('/{id}', [CartController::class, 'update'])->name('update');
    Route::delete('/{id}', [CartController::class, 'destroy'])->name('destroy');
});

// Wishlist toggle — available for guests and authenticated users
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle')->middleware('throttle:public');

// ───────────────────────────────────────────────────────────
//  Authenticated customer routes
// ───────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()?->role === 'admin') {
            return redirect('/admin');
        }
        return redirect()->route('profile.edit');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Checkout & Orders
    Route::get('/checkout', [\App\Http\Controllers\CheckoutController::class, 'show'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/payment-proof', [OrderController::class, 'uploadPaymentProof'])->name('orders.payment-proof');

    // In-App Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// Delivery Areas lookup
Route::get('/api/delivery-areas', \App\Http\Controllers\DeliveryAreaController::class)->name('delivery-areas.index');
Route::get('/delivery-areas', \App\Http\Controllers\DeliveryAreaController::class);

// ───────────────────────────────────────────────────────────
// Dedicated High-Security Admin Authentication Routes
Route::get('/admin/login', [\App\Http\Controllers\Admin\Auth\AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\Admin\Auth\AdminLoginController::class, 'login'])->name('admin.login.store');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\Auth\AdminLoginController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth', \App\Http\Middleware\EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard');

    // Dashboard Stats
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Products Management
    Route::apiResource('products', \App\Http\Controllers\Admin\ProductController::class);

    // Orders Management
    Route::get('orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/verify-payment', [\App\Http\Controllers\Admin\OrderController::class, 'verifyPayment'])->name('orders.verify-payment');

    // Categories Management
    Route::apiResource('categories', \App\Http\Controllers\Admin\CategoryController::class);

    // Delivery Areas Management
    Route::apiResource('delivery-areas', \App\Http\Controllers\Admin\DeliveryAreaController::class);

    // Customers Management
    Route::get('customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.show');

    // Settings Management
    Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';

// Health check endpoint for monitoring
Route::get('/health', function () {
    $checkDatabase = function(): bool {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    };

    $checkRedis = function(): bool {
        try {
            \Illuminate\Support\Facades\Cache::store('redis')->get('health_check');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    };

    $checkQueue = function(): bool {
        try {
            \Illuminate\Support\Facades\Queue::size();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    };

    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'services' => [
            'database' => $checkDatabase(),
            'redis' => $checkRedis(),
            'queue' => $checkQueue(),
        ],
    ]);
})->name('health');
