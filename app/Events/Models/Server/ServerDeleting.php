<?php

declare(strict_types=1);

namespace App\Events\Models\Server;

use App\Events\AbstractLogicalEvent;
use App\Models\Server;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ServerDeleting extends AbstractLogicalEvent implements ShouldBroadcastNow
{
    public function __construct(
        public Server $model,
    ) {
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('App.Models.Credential.'.$this->model->credential_id),
        ];
    }
}
