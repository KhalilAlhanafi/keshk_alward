<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = App\Models\Order::latest()->first();
if ($order && $order->transaction_number === '123456789') {
    $order->transaction_number = null;
    $order->save();
    echo "RESTORED";
}
