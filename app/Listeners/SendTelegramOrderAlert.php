<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\TelegramNotifierService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTelegramOrderAlert
{

    /**
     * Retry up to 3 times if Telegram API is temporarily unavailable.
     */
    public int $tries = 3;

    /**
     * Wait 30 s, then 60 s, then 120 s before retrying.
     */
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly TelegramNotifierService $telegram,
    ) {}

    /**
     * Handle the OrderPlaced event.
     * Any exception is caught inside TelegramNotifierService — this
     * listener will never throw and will never roll back an order.
     */
    public function handle(OrderPlaced $event): void
    {
        $this->telegram->sendOrderAlert($event->order);
    }
}
