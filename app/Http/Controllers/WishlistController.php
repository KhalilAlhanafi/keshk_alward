<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    /**
     * Helper to get active identifier for user or guest.
     */
    private function getIdentifier(Request $request): array
    {
        if (auth()->check()) {
            return ['user_id' => auth()->id()];
        }

        $sessionToken = $request->cookie('session_token');
        if (!$sessionToken) {
            $sessionToken = Str::random(40);
            cookie()->queue('session_token', $sessionToken, 60 * 24 * 30);
        }

        return ['session_token' => $sessionToken];
    }

    /**
     * Toggle a product in the wishlist.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $productId = $request->input('product_id');
        $identifier = $this->getIdentifier($request);

        // Find existing wishlist entry
        $query = Wishlist::where('product_id', $productId);
        if (isset($identifier['user_id'])) {
            $query->where('user_id', $identifier['user_id']);
        } else {
            $query->where('session_token', $identifier['session_token']);
        }

        $item = $query->first();

        if ($item) {
            $item->delete();
            $status = 'removed';
            $message = 'تمت إزالة المنتج من المفضلة.';
        } else {
            Wishlist::create(array_merge($identifier, [
                'product_id' => $productId,
            ]));
            $status = 'added';
            $message = 'تمت إضافة المنتج إلى المفضلة.';
        }

        // Count total wishlisted items
        $countQuery = Wishlist::query();
        if (isset($identifier['user_id'])) {
            $countQuery->where('user_id', $identifier['user_id']);
        } else {
            $countQuery->where('session_token', $identifier['session_token']);
        }
        $count = $countQuery->count();

        return response()->json([
            'status' => $status,
            'message' => $message,
            'count' => $count,
        ]);
    }
}
