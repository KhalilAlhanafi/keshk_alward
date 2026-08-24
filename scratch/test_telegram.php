<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = \App\Models\Order::latest()->first();

if (!$order) {
    echo "No orders found.\n";
    exit(1);
}

// Set order to Sham Cash & Awaiting Verification
$order->payment_method = \App\Enums\PaymentMethod::SHAM_CASH;
$order->payment_status = \App\Enums\PaymentStatus::AWAITING_VERIFICATION;

echo "Testing Sham Cash Telegram alert for Order #: {$order->order_number}...\n";

$notifier = app(\App\Services\TelegramNotifierService::class);
$result = $notifier->sendOrderAlert($order);

echo $result ? "SUCCESS: Sham Cash notification sent to Telegram bot!\n" : "FAILED: Could not send Telegram notification.\n";
