<?php

use App\Models\Cart;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Production Scheduled Tasks ──────────────────────────────────────────────

/**
 * Purge abandoned guest carts older than 30 days.
 * Runs daily at 02:00 Asia/Damascus.
 * Frees up rows in carts and cart_items for sessions that were never converted.
 */
Schedule::call(function () {
    $count = Cart::whereNull('user_id')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();

    \Illuminate\Support\Facades\Log::info("Purged {$count} abandoned guest carts.");
})->dailyAt('02:00')
  ->timezone('Asia/Damascus')
  ->name('purge-guest-carts')
  ->withoutOverlapping();

/**
 * Send a daily digest of pending orders to all admin users.
 * Runs at 07:00 Damascus time so the admin sees it at the start of the day.
 */
Schedule::call(function () {
    $pendingCount = \App\Models\Order::where('status', \App\Enums\OrderStatus::PENDING)->count();

    if ($pendingCount === 0) {
        return; // Nothing to report
    }

    $admins = \App\Models\User::whereHas('roles', function($q) {
        $q->whereIn('name', ['admin', 'store_manager']);
    })->get();

    foreach ($admins as $admin) {
        $admin->notify(new \App\Notifications\PendingOrdersDigestNotification($pendingCount));
    }
})->dailyAt('07:00')
  ->timezone('Asia/Damascus')
  ->name('pending-orders-digest')
  ->withoutOverlapping();

/**
 * Cancel ShamCash orders with unverified payments after 24 hours.
 * Runs every 6 hours to check for orders that haven't been verified.
 * Restores stock to inventory automatically.
 */
Schedule::command('payments:cancel-unverified 24')
  ->everySixHours()
  ->timezone('Asia/Damascus')
  ->name('cancel-unverified-payments')
  ->withoutOverlapping()
  ->runInBackground();

