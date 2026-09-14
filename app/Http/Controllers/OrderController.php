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
        } catch (\Exception $e) {
            // General catch-all for any DB errors or unknown exceptions to prevent 500 HTML response
            \Log::error('Order placement exception in controller: ' . $e->getMessage());
            $userMsg = $e instanceof \RuntimeException ? $e->getMessage() : 'عذراً، حدث خطأ أثناء معالجة الطلب. يرجى المحاولة لاحقاً.';
            return response()->json(['message' => $userMsg, 'error_debug' => $e->getMessage()], 422);
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

        $paymentMethodValue = $order->payment_method instanceof \BackedEnum ? $order->payment_method->value : (string) $order->payment_method;
        $paymentStatusValue = $order->payment_status instanceof \BackedEnum ? $order->payment_status->value : (string) $order->payment_status;

        // Only allow for ShamCash orders
        if ($paymentMethodValue !== 'sham_cash') {
            return response()->json([
                'message' => 'لا يمكن رفع إثبات الدفع، طريقة دفع الطلب ليست شام كاش.'
            ], 422);
        }

        // Cannot upload proof if already verified or paid
        if (in_array($paymentStatusValue, ['verified', 'paid'])) {
            return response()->json([
                'message' => 'تم تأكيد دفع هذا الطلب مسبقاً.'
            ], 422);
        }

        // Normalize transaction number (convert Arabic-Indic numerals and trim)
        if ($request->has('transaction_number')) {
            $raw = (string) $request->input('transaction_number');
            $eastern = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
            $western = ['0','1','2','3','4','5','6','7','8','9'];
            $clean = preg_replace('/[^0-9]/', '', str_replace($eastern, $western, $raw));
            $request->merge(['transaction_number' => $clean !== '' ? $clean : null]);
        }

        // Validate request
        $validated = $request->validate([
            'proof_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120', // Max 5MB image
            'transaction_number' => ['nullable', 'string', 'digits:9'],
        ], [
            'transaction_number.digits' => 'رقم عملية شام كاش يجب أن يتألف من 9 أرقام.',
            'proof_file.image' => 'يجب أن يكون الملف المرفوع صورة صالحة.',
            'proof_file.mimes' => 'يجب أن تكون الصورة بصيغة صالحة (jpg, png, webp).',
            'proof_file.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميغابايت.',
        ]);

        // Require at least one field
        if (!$request->hasFile('proof_file') && empty($request->input('transaction_number'))) {
            return response()->json([
                'message' => 'يرجى اختيار صورة الإيصال أو إدخال رقم العملية (9 أرقام).'
            ], 422);
        }

        try {
            // Handle file upload
            if ($request->hasFile('proof_file')) {
                $path = $request->file('proof_file')->store('payment-proofs', 'public');
                $order->payment_proof = $path;
            }
            if (!empty($request->input('transaction_number'))) {
                $order->transaction_number = $request->input('transaction_number');
            }

            // Always update status to awaiting verification and clear previous rejection
            $order->payment_status = \App\Enums\PaymentStatus::AWAITING_VERIFICATION;
            $order->rejection_reason = null;

            if ($order->status === \App\Enums\OrderStatus::CANCELLED) {
                $order->status = \App\Enums\OrderStatus::PENDING;
            }

            $order->save();

            // Send Telegram Notification
            try {
                app(\App\Services\TelegramNotifierService::class)->sendPaymentProofUploadedAlert($order);
            } catch (\Throwable $te) {
                \Log::warning('Failed sending telegram proof alert: ' . $te->getMessage());
            }

            return response()->json([
                'message' => 'تم استلام إثبات الدفع بنجاح! سيتم مراجعته وتأكيد الطلب قريباً.',
                'payment_proof' => $order->payment_proof,
                'transaction_number' => $order->transaction_number,
            ]);
        } catch (\Exception $e) {
            \Log::error('Upload Proof Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'message' => 'حدث خطأ أثناء رفع إثبات الدفع.',
                'error_debug' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order, \App\Services\TelegramNotifierService $telegram): RedirectResponse
    {
        // Policy: user can only cancel their own orders
        abort_if(auth()->id() !== $order->user_id, 403);

        if ($order->status === \App\Enums\OrderStatus::PENDING) {
            $order->status = \App\Enums\OrderStatus::CANCELLED;
            $order->save();

            // Send Telegram Notification
            $telegram->sendOrderCancelledAlert($order);

            return redirect()->back()->with('success', 'تم إلغاء الطلب بنجاح.');
        }

        return redirect()->back()->with('error', 'لا يمكن إلغاء الطلب في هذه المرحلة.');
    }
}
