<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelUnverifiedPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:cancel-unverified {hours=24 : Hours to wait before cancellation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel ShamCash orders with unverified payments after specified hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->argument('hours');
        $cutoffTime = now()->subHours($hours);

        $this->info("Checking for ShamCash orders awaiting verification since {$cutoffTime}...");

        $orders = Order::where('payment_method', PaymentMethod::SHAM_CASH)
            ->where('payment_status', PaymentStatus::AWAITING_VERIFICATION)
            ->where('created_at', '<', $cutoffTime)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found for cancellation.');
            return 0;
        }

        $this->info("Found {$orders->count()} orders to cancel.");

        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                // Update order status
                $order->status = OrderStatus::CANCELLED;
                $order->payment_status = PaymentStatus::REJECTED;
                $order->rejection_reason = 'تم إلغاء الطلب تلقائياً بسبب عدم التحقق من الدفع ضمن الفترة المحددة.';
                $order->save();

                // Restore stock
                foreach ($order->items as $item) {
                    if ($item->size_id) {
                        DB::table('product_sizes')
                            ->where('id', $item->size_id)
                            ->increment('stock', $item->quantity);
                    }
                }

                Log::info("Order #{$order->order_number} automatically cancelled due to unverified payment.");
            });

            $this->line("Cancelled order #{$order->order_number}");
        }

        $this->info("Successfully cancelled {$orders->count()} orders and restored stock.");

        return 0;
    }
}
