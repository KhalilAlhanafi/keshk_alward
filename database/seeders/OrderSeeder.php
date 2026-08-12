<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use App\Models\Product;
use App\Models\DeliveryArea;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::with('sizes', 'addons')->get();
        $deliveryAreas = DeliveryArea::all();
        $customers = User::role('customer')->get();

        if ($products->isEmpty() || $deliveryAreas->isEmpty()) {
            return;
        }

        // If no customers, create a default one
        if ($customers->isEmpty()) {
            $customer = User::create([
                'name' => 'عميل تجريبي',
                'phone' => '+963955555555',
                'email' => 'customer@example.com',
                'password' => bcrypt('password123'),
            ]);
            $customer->assignRole('customer');
            $customers->push($customer);
        }

        $statuses = [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::PROCESSING,
            OrderStatus::OUT_FOR_DELIVERY,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED,
        ];

        $paymentMethods = [PaymentMethod::COD, PaymentMethod::SHAM_CASH];
        $paymentStatuses = [PaymentStatus::PENDING, PaymentStatus::PAID, PaymentStatus::FAILED];

        for ($i = 1; $i <= 15; $i++) {
            $product = $products->random();
            $size = $product->sizes->isNotEmpty() ? $product->sizes->random() : null;
            $area = $deliveryAreas->random();
            $user = $customers->random();
            $status = $statuses[array_rand($statuses)];
            
            $basePrice = $size ? $size->price : $product->base_price;
            $quantity = rand(1, 3);
            $subtotal = $basePrice * $quantity;
            
            $addonsTotal = 0;
            $selectedAddons = [];
            
            if ($product->addons->isNotEmpty() && rand(0, 1) === 1) {
                $addonCount = rand(1, min(2, $product->addons->count()));
                $selectedAddons = $product->addons->random($addonCount);
                foreach ($selectedAddons as $addon) {
                    $addonsTotal += ($addon->price * $quantity);
                }
            }

            $deliveryFee = $area->delivery_fee;
            $grandTotal = $subtotal + $addonsTotal + $deliveryFee;

            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 24));

            $order = Order::create([
                'order_number' => '#ORD-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'recipient_name' => 'مستلم ' . $i,
                'recipient_phone' => '+9639' . rand(10000000, 99999999),
                'delivery_area_id' => $area->id,
                'delivery_address' => 'شارع رقم ' . rand(1, 100) . '، بناء ' . rand(1, 50),
                'delivery_date' => $createdAt->copy()->addDays(rand(1, 3))->format('Y-m-d'),
                'delivery_time_slot' => '12:00 - 15:00',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $grandTotal,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name_ar,
                'size_label_snapshot' => $size ? $size->label_ar : null,
                'unit_price' => $basePrice,
                'quantity' => $quantity,
                'message' => rand(0, 1) ? 'أطيب التمنيات!' : null,
            ]);

            foreach ($selectedAddons as $addon) {
                OrderItemAddon::create([
                    'order_item_id' => $orderItem->id,
                    'addon_name_snapshot' => $addon->name_ar,
                    'price_snapshot' => $addon->price,
                ]);
            }
        }
    }
}
