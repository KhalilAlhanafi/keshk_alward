<?php

use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Enums\SizeKey;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\TelegramNotifierService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer',      'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin',         'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'store_manager', 'guard_name' => 'web']);

    // Point to a fake (non-existent) bot token so calls don't hit live Telegram
    config([
        'services.telegram.bot_token' => 'FAKE_TOKEN_FOR_TESTS',
        'services.telegram.chat_id'   => '999999999',
    ]);
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Build a placed Order for Telegram tests.
 * Receives $test (the Pest $this) so it can call actingAs().
 */
function makeTelegramOrder(object $test): Order
{
    $category = Category::create(['name_ar' => 'ورد', 'slug' => 'ward-tg-' . uniqid()]);
    $product  = Product::create([
        'category_id' => $category->id,
        'name_ar'     => 'باقة جوري',
        'slug'        => 'jouri-tg-' . uniqid(),
        'sku'         => 'TG-' . uniqid(),
        'base_price'  => 75000,
        'is_active'   => true,
    ]);
    $size = ProductSize::create([
        'product_id' => $product->id,
        'size_key'   => SizeKey::MEDIUM,
        'label_ar'   => 'وسط',
        'price'      => 75000,
        'stock'      => 20,
    ]);
    $area = DeliveryArea::create([
        'city_ar'      => 'دمشق',
        'area_ar'      => 'المزة',
        'delivery_fee' => 15000,
        'is_active'    => true,
    ]);
    $user = User::create([
        'name'     => 'مستخدم تيليغرام',
        'phone'    => '+963911' . rand(100000, 999999),
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('customer');

    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create([
        'cart_id'         => $cart->id,
        'product_id'      => $product->id,
        'product_size_id' => $size->id,
        'quantity'        => 2,
    ]);

    $test->actingAs($user);

    return app(OrderService::class)->placeOrder($cart, [
        'recipient_name'     => 'سارة أحمد',
        'recipient_phone'    => '+963944111222',
        'delivery_area_id'   => $area->id,
        'delivery_address'   => 'شارع الحمرا',
        'delivery_date'      => now()->addDay()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'payment_method'     => 'cod',
    ]);
}

// ─── Tests ────────────────────────────────────────────────────────────────────

test('TelegramNotifierService sends correct request to Telegram API', function () {
    Event::fake(); // Prevent listeners from firing
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    $order = makeTelegramOrder($this);

    $service = app(TelegramNotifierService::class);
    $result  = $service->sendOrderAlert($order);

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) use ($order) {
        return str_contains($request->url(), 'api.telegram.org/botFAKE_TOKEN_FOR_TESTS/sendMessage')
            && $request['chat_id'] === '999999999'
            && str_contains($request['text'], $order->order_number)
            && str_contains($request['text'], 'سارة أحمد')
            && str_contains($request['text'], '+963944111222')
            && str_contains($request['text'], 'دمشق')
            && str_contains($request['text'], 'ل.س')
            && $request['parse_mode'] === 'HTML';
    });
});

test('TelegramNotifierService message body uses Western numerals in price', function () {
    Event::fake();
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    $order   = makeTelegramOrder($this);
    $service = app(TelegramNotifierService::class);
    $service->sendOrderAlert($order);

    Http::assertSent(function ($request) {
        // Price must contain only Western digits (0-9) and commas, never ٠١٢٣
        $text = $request['text'];
        preg_match_all('/[٠-٩]/u', $text, $matches);
        return count($matches[0]) === 0; // zero Eastern-Arabic digits found
    });
});

test('Telegram alert is dispatched as a queued listener when order is placed', function () {
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);
    Event::fake([OrderPlaced::class]);

    $order = makeTelegramOrder($this);

    // The OrderService dispatches OrderPlaced — even with Event::fake it records it
    Event::assertDispatched(OrderPlaced::class, fn ($e) => $e->order->id === $order->id);
});

test('Telegram failure does not prevent order creation', function () {
    // Simulate Telegram returning a 500 error
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => false], 500)]);
    Log::spy();

    // Place a real order (listeners run synchronously in tests)
    $order = makeTelegramOrder($this);

    // Order was created successfully despite Telegram failure
    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->status)->toBe(OrderStatus::PENDING);

    // An error was logged
    Log::shouldHaveReceived('error')
        ->withArgs(fn ($msg) => str_contains($msg, 'TelegramNotifier'));
});

test('Telegram alert skipped gracefully when token not configured', function () {
    config(['services.telegram.bot_token' => null]);
    Http::fake();
    Log::spy();

    $order = makeTelegramOrder($this);

    $service = app(TelegramNotifierService::class);
    $result  = $service->sendOrderAlert($order);

    expect($result)->toBeFalse();
    Http::assertNothingSent();
    Log::shouldHaveReceived('warning')
        ->withArgs(fn ($msg) => str_contains($msg, 'TelegramNotifier'));
});
