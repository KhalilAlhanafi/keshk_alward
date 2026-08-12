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
        $cart = Cart::where('user_id', auth()->id())
            ->with(['items.product', 'items.size', 'items.addons.addon'])
            ->firstOrFail();

        // Calculate initial totals (with 0 delivery fee initially, or default)
        $totals = $this->totalsService->calculate($cart, 0);

        return view('checkout', compact('cart', 'totals'));
    }
}
