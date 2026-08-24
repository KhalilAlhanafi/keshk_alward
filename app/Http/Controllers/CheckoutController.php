<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\CartTotalsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    protected CartTotalsService $totalsService;

    public function __construct(CartTotalsService $totalsService)
    {
        $this->totalsService = $totalsService;
    }

    /**
     * Show the checkout page.
     */
    public function show(Request $request): View
    {
        $cart = Cart::where(function($q) {
            if (auth()->check()) {
                $q->where('user_id', auth()->id());
            } else {
                $q->where('session_token', request()->cookie('session_token'));
            }
        })->with(['items.product', 'items.size'])->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $deliveryAreas = \App\Models\DeliveryArea::where('is_active', true)->get();
        $cities = $deliveryAreas->pluck('city_ar')->unique();

        $initialFee = $deliveryAreas->first()?->delivery_fee ?? 5000;
        $totals = $this->totalsService->calculate($cart, $initialFee);

        return view('checkout', compact('cart', 'totals', 'deliveryAreas', 'cities'));
    }
}
