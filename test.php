<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$orders = App\Models\Order::whereIn('id', [48, 49, 50])->get();
foreach($orders as $o) {
    echo $o->id . ' - status: ' . ($o->status instanceof \BackedEnum ? $o->status->value : $o->status) . ' - payment: ' . ($o->payment_status instanceof \BackedEnum ? $o->payment_status->value : $o->payment_status) . "\n";
}
