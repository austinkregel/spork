<?php

declare(strict_types=1);

namespace App\Events;

use App\Contracts\LogicalEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class AbstractLogicalEvent implements LogicalEvent
{
    use Dispatchable, InteractsWithSockets,  InteractsWithQueue, SerializesModels;

    public function broadcastConnections(): array
    {
        return ['pusher'];
    }
}
