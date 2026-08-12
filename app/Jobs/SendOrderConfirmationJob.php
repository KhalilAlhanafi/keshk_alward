<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted before failing.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries.
     */
    public array $backoff = [30, 60, 120];

    public function __construct(public Order $order)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $order = $this->order->loadMissing(['user', 'items.addons', 'deliveryArea']);

        if (!$order->user) {
            Log::info("Order #{$order->order_number}: no user attached, skipping customer notification.");
            return;
        }

        $order->user->notify(new OrderPlacedNotification($order));

        Log::info("Order #{$order->order_number}: customer confirmation dispatched to user #{$order->user->id}.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendOrderConfirmationJob failed for order #{$this->order->order_number}: {$e->getMessage()}");
    }
}
