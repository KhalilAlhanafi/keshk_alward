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
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load(['items.product', 'items.size']);

        $totals = $this->totalsService->calculate($cart);
        $items = $cart->items->map(function ($item) {
            $unitPrice = $item->size ? $item->size->price : ($item->product->base_price ?? 0);
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'منتج فاخر',
                'product_image' => $item->product?->primary_image_url ?? 'https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=300&q=80',
                'product_slug' => $item->product?->slug ?? $item->product_id,
                'size_id' => $item->product_size_id,
                'size_label' => $item->size ? $item->size->name : null,
                'unit_price' => $unitPrice,
                'formatted_unit_price' => format_money($unitPrice),
                'quantity' => $item->quantity,
                'message' => $item->message,
                'subtotal' => $unitPrice * $item->quantity,
                'formatted_subtotal' => format_money($unitPrice * $item->quantity),
            ];
        });

        $cartCount = $cart->items->sum('quantity');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'items' => $items,
                'totals' => $totals,
                'cart_count' => $cartCount,
            ]);
        }

        return view('cart', compact('cart', 'items', 'totals', 'cartCount'));
    }

    /**
     * Add an item to the cart.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = $this->getOrCreateCart($request);
        $productId = $request->input('product_id');
        $sizeId = $request->input('size_id') ?: $request->input('product_size_id');
        $qty = $request->input('quantity', 1);

        $product = Product::with('sizes')->findOrFail($productId);

        if (!$sizeId && $product->sizes->count() > 0) {
            $sizeId = $product->sizes->first()->id;
        }

        // Find or create item
        $itemQuery = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId);
            
        if ($sizeId) {
            $itemQuery->where('product_size_id', $sizeId);
        }

        $item = $itemQuery->first();

        if ($item) {
            $item->quantity += $qty;
            if ($request->filled('personal_message') || $request->filled('message')) {
                $item->message = $request->input('personal_message') ?: $request->input('message');
            }
            $item->save();
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'product_size_id' => $sizeId,
                'quantity' => $qty,
                'message' => $request->input('personal_message') ?: $request->input('message'),
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
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($id);
        
        $item->quantity = (int) $request->input('quantity');
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
