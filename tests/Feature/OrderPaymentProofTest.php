<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderPaymentProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_user_can_upload_payment_proof_and_transaction_number()
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+963912345678',
            'password' => bcrypt('password'),
        ]);

        $area = DeliveryArea::create([
            'city_ar' => 'دمشق',
            'area_ar' => 'المزة',
            'delivery_fee' => 5000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => '#ORD-2026-TEST',
            'recipient_name' => 'Recipient Name',
            'recipient_phone' => '+963912345678',
            'delivery_area_id' => $area->id,
            'delivery_address' => 'Test Address',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '10:00',
            'subtotal' => 50000,
            'delivery_fee' => 5000,
            'total' => 55000,
            'payment_method' => PaymentMethod::SHAM_CASH,
            'payment_status' => PaymentStatus::AWAITING_VERIFICATION,
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->actingAs($user)
            ->postJson(route('orders.payment-proof', $order->id), [
                'proof_file' => $file,
                'transaction_number' => '123456789',
            ]);

        $response->assertStatus(200);
        $order->refresh();

        $this->assertNotNull($order->payment_proof);
        $this->assertEquals('123456789', $order->transaction_number);
    }

    public function test_arabic_indic_numerals_are_converted_to_western_digits()
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Arabic Digits User',
            'email' => 'digits@example.com',
            'phone' => '+963912345679',
            'password' => bcrypt('password'),
        ]);

        $area = DeliveryArea::create([
            'city_ar' => 'دمشق',
            'area_ar' => 'المزة',
            'delivery_fee' => 5000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => '#ORD-2026-DIGITS',
            'recipient_name' => 'Recipient Name',
            'recipient_phone' => '+963912345679',
            'delivery_area_id' => $area->id,
            'delivery_address' => 'Test Address',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '10:00',
            'subtotal' => 50000,
            'delivery_fee' => 5000,
            'total' => 55000,
            'payment_method' => PaymentMethod::SHAM_CASH,
            'payment_status' => PaymentStatus::AWAITING_VERIFICATION,
        ]);

        // Submit Eastern Arabic digits: ٩٨٧٦٥٤٣٢١
        $response = $this->actingAs($user)
            ->postJson(route('orders.payment-proof', $order->id), [
                'transaction_number' => '٩٨٧٦٥٤٣٢١',
            ]);

        $response->assertStatus(200);
        $order->refresh();

        $this->assertEquals('987654321', $order->transaction_number);
    }

    public function test_user_can_reupload_proof_if_payment_was_rejected()
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Rejected User',
            'email' => 'rejected@example.com',
            'phone' => '+963912345680',
            'password' => bcrypt('password'),
        ]);

        $area = DeliveryArea::create([
            'city_ar' => 'دمشق',
            'area_ar' => 'المزة',
            'delivery_fee' => 5000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => '#ORD-2026-REJECTED',
            'recipient_name' => 'Recipient Name',
            'recipient_phone' => '+963912345680',
            'delivery_area_id' => $area->id,
            'delivery_address' => 'Test Address',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '10:00',
            'subtotal' => 50000,
            'delivery_fee' => 5000,
            'total' => 55000,
            'payment_method' => PaymentMethod::SHAM_CASH,
            'payment_status' => PaymentStatus::REJECTED,
            'rejection_reason' => 'صورة غير واضحة',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('orders.payment-proof', $order->id), [
                'transaction_number' => '111222333',
            ]);

        $response->assertStatus(200);
        $order->refresh();

        $this->assertEquals('111222333', $order->transaction_number);
        $this->assertEquals(PaymentStatus::AWAITING_VERIFICATION, $order->payment_status);
        $this->assertNull($order->rejection_reason);
    }

    public function test_storage_serve_download_forces_attachment()
    {
        Storage::disk('public')->put('test.jpg', 'fake-content');

        $response = $this->get(route('storage.serve', ['path' => 'test.jpg', 'download' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=test.jpg');
    }
}
