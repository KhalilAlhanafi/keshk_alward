<?php

use App\Models\Order;
use App\Models\User;
use App\Models\DeliveryArea;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Listeners\SendCustomerConfirmation;
use App\Listeners\NotifyAdminOfNewOrder;
use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\NotifyAdminNewOrderJob;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\NewOrderAdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeTestUser(string $role = 'customer', string $phone = '+963911111111', string $email = null): User
{
    $email = $email ?? 'test-' . uniqid() . '@example.com';
    $user = User::create([
        'name' => 'تجريبي',
        'phone' => $phone,
        'email' => $email,
        'password' => bcrypt('password'),
    ]);
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user->assignRole($role);
    return $user;
}

// ─── Queue Tests ─────────────────────────────────────────────────────────────

test('OrderPlaced event dispatches queued listeners on notifications queue', function () {
    Queue::fake();

    $user = makeTestUser('customer');
    $area = DeliveryArea::create([
        'city_ar' => 'دمشق', 'area_ar' => 'الشعلان', 'delivery_fee' => 10000, 'is_active' => true
    ]);

    $order = Order::create([
        'order_number' => '#ORD-EVENT-0001',
        'user_id' => $user->id,
        'recipient_name' => 'سليم',
        'recipient_phone' => '+963999999999',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'شارع الحمرا',
        'delivery_date' => now()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'subtotal' => 50000,
        'delivery_fee' => 10000,
        'total' => 60000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::COD,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    event(new OrderPlaced($order));

    // Assert that the queued listeners are pushed to the queue
    Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class);
});

// ─── Notification Dispatch Tests ──────────────────────────────────────────────

test('SendOrderConfirmationJob dispatches OrderPlacedNotification to customer', function () {
    Notification::fake();

    $user = makeTestUser('customer');
    $area = DeliveryArea::create([
        'city_ar' => 'دمشق', 'area_ar' => 'الشعلان', 'delivery_fee' => 10000, 'is_active' => true
    ]);

    $order = Order::create([
        'order_number' => '#ORD-JOB-0001',
        'user_id' => $user->id,
        'recipient_name' => 'سليم',
        'recipient_phone' => '+963999999999',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'شارع الحمرا',
        'delivery_date' => now()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'subtotal' => 50000,
        'delivery_fee' => 10000,
        'total' => 60000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::COD,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $job = new SendOrderConfirmationJob($order);
    $job->handle();

    Notification::assertSentTo(
        $user,
        OrderPlacedNotification::class,
        function ($notification) use ($order) {
            return $notification->order->id === $order->id;
        }
    );
});

test('NotifyAdminNewOrderJob dispatches NewOrderAdminNotification to all admins', function () {
    Notification::fake();

    $admin1 = makeTestUser('admin', '+963911111112');
    $admin2 = makeTestUser('store_manager', '+963911111113');
    $customer = makeTestUser('customer', '+963911111114');

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق', 'area_ar' => 'الشعلان', 'delivery_fee' => 10000, 'is_active' => true
    ]);

    $order = Order::create([
        'order_number' => '#ORD-JOB-0002',
        'recipient_name' => 'سليم',
        'recipient_phone' => '+963999999999',
        'delivery_area_id' => $area->id,
        'delivery_address' => 'شارع الحمرا',
        'delivery_date' => now()->format('Y-m-d'),
        'delivery_time_slot' => '12:00 - 15:00',
        'subtotal' => 50000,
        'delivery_fee' => 10000,
        'total' => 60000,
        'status' => OrderStatus::PENDING,
        'payment_method' => PaymentMethod::COD,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $job = new NotifyAdminNewOrderJob($order);
    $job->handle();

    Notification::assertSentTo([$admin1, $admin2], NewOrderAdminNotification::class);
    Notification::assertNotSentTo($customer, NewOrderAdminNotification::class);
});

// ─── API Endpoints Tests ─────────────────────────────────────────────────────

test('user can fetch, read, and delete in-app notifications via API', function () {
    $user = makeTestUser('customer');

    $area = DeliveryArea::create([
        'city_ar' => 'دمشق', 'area_ar' => 'الشعلان', 'delivery_fee' => 10000, 'is_active' => true
    ]);

    // Create a mock notification inside database notifications table
    $user->notify(new OrderPlacedNotification(
        Order::create([
            'order_number' => '#ORD-NOTIF-0001',
            'recipient_name' => 'سليم',
            'recipient_phone' => '+963999999999',
            'delivery_area_id' => $area->id,
            'delivery_address' => 'شارع الحمرا',
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_time_slot' => '12:00 - 15:00',
            'subtotal' => 50000,
            'delivery_fee' => 10000,
            'total' => 60000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::COD,
            'payment_status' => PaymentStatus::PENDING,
        ])
    ));

    $this->actingAs($user);

    // 1. Fetch
    $response = $this->getJson('/notifications');
    $response->assertStatus(200)
        ->assertJsonPath('unread_count', 1)
        ->assertJsonCount(1, 'data');

    $notifId = $response->json('data.0.id');

    // 2. Mark Read
    $this->postJson("/notifications/{$notifId}/read")
        ->assertStatus(200);

    $this->getJson('/notifications')
        ->assertJsonPath('unread_count', 0);

    // 3. Delete
    $this->deleteJson("/notifications/{$notifId}")
        ->assertStatus(200);

    $this->getJson('/notifications')
        ->assertJsonCount(0, 'data');
});
