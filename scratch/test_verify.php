<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\User;
use App\Http\Controllers\Admin\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$admin = User::where('role', 'admin')->first();
Auth::login($admin);

$order = Order::where('payment_method', 'sham_cash')->latest()->first();

if (!$order) {
    echo "No sham_cash order found\n";
    exit;
}

echo "Testing verify for order #{$order->order_number} (ID: {$order->id})...\n";

$req = Request::create("/admin/orders/{$order->id}/verify-payment", "POST", [
    "action" => "verify"
]);

$controller = app(OrderController::class);
$response = $controller->verifyPayment($req, $order);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getContent() . "\n";
