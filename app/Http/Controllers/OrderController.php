<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Place a new order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $cart = Cart::where(function($q) use ($request) {
            if (auth()->check()) {
                $q->where('user_id', auth()->id());
            } else {
                $q->where('session_token', $request->cookie('session_token'));
            }
        })
        ->with(['items.product', 'items.size', 'items.addons.addon'])
        ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'سلة التسوق فارغة.'], 422);
        }

        try {
            $order = $this->orderService->placeOrder($cart, $request->validated());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $shamCashWallet = null;
        if ($order->payment_method->value === 'sham_cash') {
            $shamCashWallet = Setting::get('sham_cash_wallet_code', '0963900000000');
        }

        return response()->json([
            'message' => 'تم إتمام طلبك بنجاح!',
            'order_number' => $order->order_number,
            'redirect_url' => route('orders.show', $order->id),
            'sham_cash_wallet_code' => $shamCashWallet,
        ]);
    }

    /**
     * Show a specific order.
     */
    public function show(Order $order): View
    {
        // Policy: user can only see their own orders
        abort_if(auth()->id() !== $order->user_id, 403);

        $order->load(['items.addons', 'deliveryArea', 'transactions']);

        $shamCashWallet = null;
        if ($order->payment_method->value === 'sham_cash') {
            $shamCashWallet = Setting::get('sham_cash_wallet_code', '0963900000000');
        }

        return view('orders.show', compact('order', 'shamCashWallet'));
    }

    /**
     * Upload payment proof for ShamCash orders.
     */
    public function uploadPaymentProof(Request $request, Order $order): JsonResponse
    {
        // Policy: user can only upload proof for their own orders
        abort_if(auth()->id() !== $order->user_id, 403);

        // Only allow for ShamCash orders awaiting verification
        if ($order->payment_method->value !== 'sham_cash' || 
            $order->payment_status->value !== 'awaiting_verification') {
            return response()->json([
                'message' => 'لا يمكن رفع إثبات الدفع لهذا الطلب.'
            ], 422);
        }

        // Validate request
        $validated = $request->validate([
            'proof_file' => 'nullable|image|max:5120', // Max 5MB image
            'transaction_number' => 'nullable|string|max:100',
        ]);

        // Require at least one field
        if (!$request->hasFile('proof_file') && !$request->transaction_number) {
            return response()->json([
                'message' => 'يجب رفع صورة الإيصال أو إدخال رقم العملية.'
            ], 422);
        }

        try {
            // Handle file upload
            if ($request->hasFile('proof_file')) {
                $path = $request->file('proof_file')->store('payment-proofs', 'public');
                $order->payment_proof = $path;
            }

            // Handle transaction number
            if ($request->transaction_number) {
                $order->payment_proof = $request->transaction_number;
            }

            $order->save();

            return response()->json([
                'message' => 'تم رفع إثبات الدفع بنجاح. سيتم مراجعته قريباً.',
                'payment_proof' => $order->payment_proof,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء رفع إثبات الدفع.'
            ], 500);
        }
    }
}
