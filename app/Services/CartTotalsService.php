<?php

namespace App\Services;

use App\Models\Cart;

class CartTotalsService
{
    /**
     * Calculate cart totals.
     *
     * @param Cart $cart
     * @param int $deliveryFee
     * @return array{subtotal: int, addons_total: int, delivery_fee: int, total: int, formatted_subtotal: string, formatted_addons_total: string, formatted_delivery_fee: string, formatted_total: string}
     */
    public function calculate(Cart $cart, int $deliveryFee = 0): array
    {
        $subtotal = 0;
        $addonsTotal = 0;

        // Eager load items, product, size, and addons to prevent N+1 queries
        $cart->loadMissing('items.product', 'items.size', 'items.addons.addon');

        foreach ($cart->items as $item) {
            // Price is determined by the specific size, or falls back to product's base price
            $unitPrice = $item->size ? $item->size->price : $item->product->base_price;
            $subtotal += $unitPrice * $item->quantity;

            // Calculate addons selected for this item
            foreach ($item->addons as $cartItemAddon) {
                $addonsTotal += $cartItemAddon->price_snapshot * $item->quantity;
            }
        }

        $total = $subtotal + $addonsTotal + $deliveryFee;

        return [
            'subtotal' => $subtotal,
            'addons_total' => $addonsTotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'formatted_subtotal' => format_money($subtotal),
            'formatted_addons_total' => format_money($addonsTotal),
            'formatted_delivery_fee' => format_money($deliveryFee),
            'formatted_total' => format_money($total),
        ];
    }
}
