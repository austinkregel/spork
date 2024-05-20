<?php

declare(strict_types=1);

namespace App\Events\Models\Account;

use App\Events\AbstractLogicalEvent;
use App\Models\Finance\Account;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AccountUpdated extends AbstractLogicalEvent implements ShouldBroadcast
{
    public function __construct(
        public Account $model,
    ) {
    }

    public function broadcastOn(): array
    {
        $this->model->load('credential');

        return [
            new PrivateChannel('App.Models.User.'.$this->model->credential->user_id),
        ];
    }
}
