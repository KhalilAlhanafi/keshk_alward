<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'deliveryArea']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Search by order_number, customer name/phone, or recipient name/phone
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(15);

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

    /**
     * Display the specified order details.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['items.addons', 'user', 'deliveryArea', 'transactions']);
        return response()->json($order);
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
        // Check policy - only admins can verify payments
        Gate::authorize('verifyPayment', $order);

        // Only allow for ShamCash orders awaiting verification
        if ($order->payment_method->value !== 'sham_cash' || 
            $order->payment_status->value !== 'awaiting_verification') {
            return response()->json([
                'message' => 'هذا الطلب غير مؤهل للتحقق من الدفع.'
            ], 422);
        }

        $request->validate([
            'action' => 'required|in:verify,reject',
            'rejection_reason' => 'required_if:action,reject|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $order) {
            if ($request->action === 'verify') {
                // Verify payment
                $order->payment_status = PaymentStatus::VERIFIED;
                $order->payment_verified_at = now();
                $order->verified_by = auth()->id();
                $order->status = OrderStatus::CONFIRMED; // Move to confirmed
                $order->rejection_reason = null;

                Log::channel('payment')->info('Payment verified', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'verified_by' => auth()->id(),
                    'amount' => $order->total,
                ]);
            } else {
                // Reject payment
                $order->payment_status = PaymentStatus::REJECTED;
                $order->rejection_reason = $request->rejection_reason;
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
                    'rejected_by' => auth()->id(),
                    'rejection_reason' => $request->rejection_reason,
                    'amount' => $order->total,
                ]);
            }

            $order->save();

            return response()->json([
                'message' => $request->action === 'verify' 
                    ? 'تم التحقق من الدفع بنجاح وتأكيد الطلب.' 
                    : 'تم رفض الدفع وإلغاء الطلب.',
                'order' => $order->load(['user', 'deliveryArea', 'verifiedBy']),
            ]);
        });
    }
}
