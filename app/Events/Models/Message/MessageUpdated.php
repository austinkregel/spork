<?php

declare(strict_types=1);

namespace App\Events\Models\Message;

use App\Events\AbstractLogicalEvent;
use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageUpdated extends AbstractLogicalEvent implements ShouldBroadcast
{
    public function __construct(
        public Message $model,
    ) {}

    public function broadcastOn(): array
    {
        $this->model->load('credential');

        return [
            new PrivateChannel('App.Models.User.'.$this->model->credential->user_id),
        ];
    }
}
