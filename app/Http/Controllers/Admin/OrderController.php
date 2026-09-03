<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with filters.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $query = Order::with(['user', 'deliveryArea']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ]
            ]);
        }

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Display the specified order details.
     */
    public function show(Request $request, Order $order): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $order->load(['items.addons', 'user', 'deliveryArea', 'transactions']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($order);
        }

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Transition order status.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ]);

        $oldStatus = $order->status;
        $newStatus = OrderStatus::from($request->input('status'));

        $order->status = $newStatus;

        if ($request->filled('payment_status')) {
            $order->payment_status = PaymentStatus::from($request->input('payment_status'));
        }

        // Auto-mark as paid if delivered (convenience helper)
        if ($newStatus === OrderStatus::DELIVERED && $order->payment_status !== PaymentStatus::PAID) {
            $order->payment_status = PaymentStatus::PAID;
        }

        $order->save();

        // Notify customer
        try {
            $statusLabel = $newStatus->labelAr();
            $msg = "تم تحديث حالة طلبك #{$order->order_number} إلى: {$statusLabel}.";
            $order->user?->notify(new \App\Notifications\OrderStatusUpdatedNotification($order, $msg, 'status_updated'));
        } catch (\Throwable $e) {
            Log::warning("Failed to notify user for order status update: " . $e->getMessage());
        }

        // Trigger notification log
        Log::info("Order #{$order->order_number} status changed from {$oldStatus->value} to {$newStatus->value}. Triggering customer notification.");

        return response()->json([
            'message' => 'تم تحديث حالة الطلب بنجاح.',
            'order' => $order->load(['user', 'deliveryArea']),
        ]);
    }

    /**
     * Verify or reject ShamCash payment.
     */
    public function verifyPayment(Request $request, Order $order): JsonResponse
    {
        // Check policy - only admins and store managers can verify payments
        Gate::authorize('verifyPayment', $order);

        $paymentMethodVal = $order->payment_method instanceof \BackedEnum 
            ? $order->payment_method->value 
            : (string) $order->payment_method;

        // Only allow for ShamCash orders
        if ($paymentMethodVal !== 'sham_cash') {
            return response()->json([
                'message' => 'هذا الطلب غير مخصص لدفع شام كاش.'
            ], 422);
        }

        $request->validate([
            'action' => 'required|in:verify,reject',
            'rejection_reason' => 'nullable|required_if:action,reject|string|max:500',
        ], [
            'rejection_reason.required_if' => 'يرجى كتابة سبب الرفض.',
            'rejection_reason.string' => 'يجب أن يكون سبب الرفض نصاً صالحاً.',
        ]);

        return DB::transaction(function () use ($request, $order) {
            $action = $request->input('action');
            $rejectionReason = $request->input('rejection_reason');

            if ($action === 'verify') {
                // Verify payment
                $order->payment_status = PaymentStatus::VERIFIED;
                $order->payment_verified_at = now();
                $order->verified_by = Auth::id();
                $order->status = OrderStatus::CONFIRMED; // Move to confirmed
                $order->rejection_reason = null;

                Log::channel('payment')->info('Payment verified', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'verified_by' => Auth::id(),
                    'amount' => $order->total,
                ]);

                $notificationMsg = "تم التحقق من دفع شام كاش وتأكيد طلبك #{$order->order_number} بنجاح!";
            } else {
                // Reject payment
                $order->payment_status = PaymentStatus::REJECTED;
                $order->rejection_reason = $rejectionReason;
                $order->status = OrderStatus::CANCELLED; // Cancel the order

                // Restore stock
                foreach ($order->items as $item) {
                    if ($item->size_id) {
                        DB::table('product_sizes')
                            ->where('id', $item->size_id)
                            ->increment('stock', $item->quantity);
                    }
                }

                Log::channel('payment')->warning('Payment rejected', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'rejected_by' => Auth::id(),
                    'rejection_reason' => $rejectionReason,
                    'amount' => $order->total,
                ]);

                $notificationMsg = "تم رفض إثبات الدفع وإلغاء الطلب #{$order->order_number}." . ($rejectionReason ? " سبب الرفض: {$rejectionReason}" : "");
            }

            $order->save();

            // Notify customer in database and mail
            try {
                $order->user?->notify(new \App\Notifications\OrderStatusUpdatedNotification($order, $notificationMsg, 'payment_review'));
            } catch (\Throwable $e) {
                Log::warning("Failed to notify user for order payment verification: " . $e->getMessage());
            }

            return response()->json([
                'message' => $action === 'verify' 
                    ? 'تم التحقق من الدفع بنجاح وتأكيد الطلب.' 
                    : 'تم رفض الدفع وإلغاء الطلب.',
                'order' => $order->load(['user', 'deliveryArea', 'verifiedBy']),
            ]);
        });
    }
}
