<?php

$user = \App\Models\User::role('customer')->first();
$cart = \App\Models\Cart::create(['user_id' => $user->id]);

$product = \App\Models\Product::first();
$size = \App\Models\ProductSize::where('product_id', $product->id)->first();

\App\Models\CartItem::create([
    'cart_id' => $cart->id,
    'product_id' => $product->id,
    'product_size_id' => $size->id,
    'quantity' => 1
]);

$area = \App\Models\DeliveryArea::first();

auth()->login($user);

app(\App\Services\OrderService::class)->placeOrder($cart, [
    'recipient_name' => 'تجربة تيليغرام',
    'recipient_phone' => '+963999999999',
    'delivery_area_id' => $area->id,
    'delivery_address' => 'شارع التجربة',
    'delivery_date' => now()->addDay()->format('Y-m-d'),
    'delivery_time_slot' => '12:00 - 15:00',
    'payment_method' => 'cod',
]);

echo "Order placed successfully! Notification dispatched.\n";
