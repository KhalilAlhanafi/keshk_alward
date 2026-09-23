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
    public function show(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if (!\App\Models\Setting::get('orders_enabled', true)) {
            return redirect()->route('cart.index')->with('error', \App\Models\Setting::get('orders_closed_message', 'نعتذر منكم، تم إيقاف استقبال الطلبات مؤقتاً لنفاد البضاعة.'));
        }

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

        $totals = $this->totalsService->calculate($cart, 0);

        return view('checkout', compact('cart', 'totals', 'deliveryAreas', 'cities'));
    }
}
