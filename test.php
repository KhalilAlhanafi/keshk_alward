<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $order = App\Models\Order::latest()->first();
    if ($order) {
        Auth::login($order->user);
        $request = Illuminate\Http\Request::create('/orders/' . $order->id . '/payment-proof', 'POST', ['transaction_number' => '123456789']);
        $controller = app(App\Http\Controllers\OrderController::class);
        $response = $controller->uploadPaymentProof($request, $order);
        echo "Response Content:\n";
        echo $response->getContent() . "\n";
    } else {
        echo "No orders in DB\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
