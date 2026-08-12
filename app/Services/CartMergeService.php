<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

class CartMergeService
{
    /**
     * Merge the guest cart associated with the session token in cookies into the authenticated user's cart.
     *
     * @param User $user
     * @param string|null $sessionToken
     * @return void
     */
    public function merge(User $user, ?string $sessionToken = null): void
    {
        $sessionToken = $sessionToken ?? Cookie::get('session_token');

        if (!$sessionToken) {
            return;
        }

        // Find guest cart
        $guestCart = Cart::where('session_token', $sessionToken)->first();
        if (!$guestCart) {
            return;
        }

        // Find or create user cart
        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Merge guest items into user cart
        $guestItems = $guestCart->items;

        foreach ($guestItems as $guestItem) {
            // Check if user cart already has the same product & size
            $existingUserItem = CartItem::where('cart_id', $userCart->id)
                ->where('product_id', $guestItem->product_id)
                ->where('product_size_id', $guestItem->product_size_id)
                ->first();

            if ($existingUserItem) {
                // Sum quantities
                $existingUserItem->quantity += $guestItem->quantity;
                $existingUserItem->save();
                $guestItem->delete();
            } else {
                // Associate item with user's cart
                $guestItem->cart_id = $userCart->id;
                $guestItem->save();
            }
        }

        // Delete guest cart
        $guestCart->delete();

        // Clear the session token cookie
        Cookie::queue(Cookie::forget('session_token'));
    }
}
