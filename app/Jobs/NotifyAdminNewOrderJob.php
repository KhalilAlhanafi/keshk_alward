<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderAdminNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyAdminNewOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public Order $order)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        // Notify all users that have admin or store_manager role
        $admins = User::role(['admin', 'store_manager'])->get();

        if ($admins->isEmpty()) {
            Log::warning("NotifyAdminNewOrderJob: no admin/store_manager users found to notify.");
            return;
        }

        $notification = new NewOrderAdminNotification($this->order);

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        Log::info("Order #{$this->order->order_number}: admin notification dispatched to {$admins->count()} user(s).");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("NotifyAdminNewOrderJob failed for order #{$this->order->order_number}: {$e->getMessage()}");
    }
}
