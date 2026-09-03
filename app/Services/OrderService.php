<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Cart;
use App\Models\DeliveryArea;
use App\Models\ProductSize;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected CartTotalsService $totalsService;

    public function __construct(CartTotalsService $totalsService)
    {
        $this->totalsService = $totalsService;
    }

    /**
     * Generate a sequential, human-readable order number.
     * Format: #ORD-YYYYMMDD-XXXX (e.g. #ORD-20260809-0001)
     */
    public function generateOrderNumber(): string
    {
        $datePrefix = '#ORD-' . now()->format('Ymd') . '-';

        // PostgreSQL does not support lockForUpdate() with aggregate functions like count()
        // Use count without lock - race conditions are acceptable for order number generation
        // as duplicate order numbers will be caught by the unique constraint
        $count = Order::where('order_number', 'like', $datePrefix . '%')->count();
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return $datePrefix . $sequence;
    }

    /**
     * Place an order from a cart.
     *
     * @param Cart $cart
     * @param array $data
     * @throws \Exception
     */
    public function placeOrder(Cart $cart, array $data): Order
    {
        // Eager load everything needed
        $cart->loadMissing('items.product', 'items.size', 'items.addons.addon');
        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('لا يمكن إتمام طلب بسلة فارغة.');
        }

        // Validate delivery area exists and is active
        $deliveryArea = DeliveryArea::where('id', $data['delivery_area_id'])
            ->where('is_active', true)
            ->firstOrFail();

        // Calculate totals using server-side prices (source of truth)
        $totals = $this->totalsService->calculate($cart, $deliveryArea->delivery_fee);

        // Handle idempotency - prevent duplicate orders
        $idempotencyKey = $data['idempotency_key'] ?? Str::uuid();
        
        // Check if order already exists with this idempotency key
        $existingOrder = Order::where('idempotency_key', $idempotencyKey)->first();
        if ($existingOrder) {
            return $existingOrder; // Return existing order instead of creating duplicate
        }

        try {
            return DB::transaction(function () use ($cart, $data, $totals, $deliveryArea, $idempotencyKey) {
            
            // 1. Lock all affected product sizes atomically to prevent race conditions
            $sizeIds = $cart->items->pluck('product_size_id')->unique()->filter();
            $lockedSizes = ProductSize::whereIn('id', $sizeIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 2. Validate stock availability using locked instances
            foreach ($cart->items as $item) {
                if (!$item->product_size_id) {
                    continue; // Skip items without size (if applicable)
                }
                
                $size = $lockedSizes->get($item->product_size_id);
                if (!$size || $size->stock < $item->quantity) {
                    $prodName = $item->product?->name ?? $item->product?->name_ar ?? 'المحدد';
                    throw new \RuntimeException("الكمية المطلوبة من المنتج '{$prodName}' غير متوفرة في المخزون حالياً.");
                }
            }

            // 3. Generate the sequential order number
            $orderNumber = $this->generateOrderNumber();

            // 4. Determine payment status based on payment method
            $paymentMethod = PaymentMethod::from($data['payment_method']);
            $paymentStatus = PaymentStatus::PENDING;
            
            // For ShamCash, set to awaiting verification
            if ($paymentMethod === PaymentMethod::SHAM_CASH) {
                $paymentStatus = PaymentStatus::AWAITING_VERIFICATION;
            }

            // 5. Create the order
            $order = Order::create([
                'order_number' => $orderNumber,
                'idempotency_key' => $idempotencyKey,
                'user_id' => auth()->id(),
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'delivery_area_id' => $deliveryArea->id,
                'delivery_address' => $data['delivery_address'],
                'delivery_date' => $data['delivery_date'],
                'delivery_time_slot' => $data['delivery_time_slot'],
                'card_message' => $data['card_message'] ?? null,
                'subtotal' => $totals['subtotal'],
                'delivery_fee' => $totals['delivery_fee'],
                'total' => $totals['total'],
                'status' => OrderStatus::PENDING,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
            ]);

            // 6. Create order items and snapshot prices + addons
            foreach ($cart->items as $item) {
                $unitPrice = $item->size ? $item->size->price : $item->product->base_price;
                
                $orderItem = $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name_snapshot' => $item->product->name_ar,
                    'size_label_snapshot' => $item->size ? $item->size->label_ar : 'لا يوجد',
                    'wrapping_color' => $item->wrapping_color,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'message' => $item->message,
                ]);

                // Snapshot chosen addons
                foreach ($item->addons as $itemAddon) {
                    $orderItem->addons()->create([
                        'addon_name_snapshot' => $itemAddon->addon->name_ar,
                        'price_snapshot' => $itemAddon->price_snapshot,
                    ]);
                }

                // 7. Decrement the stock level using the locked instance
                if ($item->product_size_id && isset($lockedSizes[$item->product_size_id])) {
                    $lockedSizes[$item->product_size_id]->decrement('stock', $item->quantity);
                }
            }

            // 8. Clear the cart
            foreach ($cart->items as $item) {
                $item->addons()->delete();
            }
            $cart->items()->delete();
            $cart->delete();

            // 9. Dispatch OrderPlaced event
            event(new OrderPlaced($order));

            // Log successful order creation
            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'total' => $order->total,
                'payment_method' => $order->payment_method->value,
                'payment_status' => $order->payment_status->value,
            ]);

            return $order;
        });
        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
