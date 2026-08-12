<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\NotifyAdminNewOrderJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdminOfNewOrder implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(OrderPlaced $event): void
    {
        NotifyAdminNewOrderJob::dispatch($event->order);
    }
}
