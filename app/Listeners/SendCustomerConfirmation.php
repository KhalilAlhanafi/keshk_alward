<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderConfirmationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCustomerConfirmation implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(OrderPlaced $event): void
    {
        SendOrderConfirmationJob::dispatch($event->order);
    }
}
