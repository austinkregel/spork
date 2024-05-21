<?php

declare(strict_types=1);

namespace App\Events\Models\JobBatch;

use App\Events\AbstractLogicalEvent;
use App\Models\JobBatch;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class JobBatchUpdated extends AbstractLogicalEvent implements ShouldBroadcast
{
    public function __construct(
        public JobBatch $batch
    ) {
    }

    public function broadcastOn(): array
    {
        return User::query()
            ->whereIn('email', config('auth.admin_emails'))
            ->get()
            ->map(fn (User $user) => new PrivateChannel('App.Models.User.'.$user->id))
            ->toArray();
    }
}
