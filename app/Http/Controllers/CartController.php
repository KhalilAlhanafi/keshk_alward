<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Services\CartTotalsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    protected CartTotalsService $totalsService;

    public function __construct(CartTotalsService $totalsService)
    {
        $this->totalsService = $totalsService;
    }

    /**
     * Helper to get or create active cart for user or guest.
     */
    private function getOrCreateCart(Request $request): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        $sessionToken = $request->cookie('session_token');
        if (!$sessionToken) {
            $sessionToken = Str::random(40);
            // Queue session token cookie for 30 days
            cookie()->queue('session_token', $sessionToken, 60 * 24 * 30);
        }

        return Cart::firstOrCreate(['session_token' => $sessionToken]);
    }

    /**
     * Display the cart items and totals.
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        
        // Eager load relations to prevent N+1 queries
        $cart->load(['items.product', 'items.size']);

        $totals = $this->totalsService->calculate($cart);

        return response()->json([
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name_ar,
                    'size_id' => $item->product_size_id,
                    'size_label' => $item->size ? $item->size->label_ar : null,
                    'unit_price' => $item->size ? $item->size->price : $item->product->base_price,
                    'formatted_unit_price' => format_money($item->size ? $item->size->price : $item->product->base_price),
                    'quantity' => $item->quantity,
                    'message' => $item->message,
                    'subtotal' => ($item->size ? $item->size->price : $item->product->base_price) * $item->quantity,
                    'formatted_subtotal' => format_money(($item->size ? $item->size->price : $item->product->base_price) * $item->quantity),
                ];
            }),
            'totals' => $totals,
        ]);
    }

    /**
     * Add an item to the cart.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_size_id' => ['required', 'exists:product_sizes,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = $this->getOrCreateCart($request);
        $productId = $request->input('product_id');
        $sizeId = $request->input('product_size_id');
        $qty = $request->input('quantity');

        // Check stock levels
        $size = ProductSize::findOrFail($sizeId);
        if ($size->stock < $qty) {
            return response()->json(['message' => 'الكمية المطلوبة غير متوفرة في المخزون.'], 422);
        }

        // Find or create item
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('product_size_id', $sizeId)
            ->first();

        if ($item) {
            if ($size->stock < ($item->quantity + $qty)) {
                return response()->json(['message' => 'الكمية الإجمالية تتجاوز المخزون المتوفر.'], 422);
            }
            $item->quantity += $qty;
            if ($request->has('message')) {
                $item->message = $request->input('message');
            }
            $item->save();
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'product_size_id' => $sizeId,
                'quantity' => $qty,
                'message' => $request->input('message'),
            ]);
        }

        return $this->index($request);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($id);
        $qty = $request->input('quantity');

        // Check stock
        $size = ProductSize::findOrFail($item->product_size_id);
        if ($size->stock < $qty) {
            return response()->json(['message' => 'الكمية المطلوبة غير متوفرة في المخزون.'], 422);
        }

        $item->quantity = $qty;
        if ($request->has('message')) {
            $item->message = $request->input('message');
        }
        $item->save();

        return $this->index($request);
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($id);
        $item->delete();

        return $this->index($request);
    }
}
