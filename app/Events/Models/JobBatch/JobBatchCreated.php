<?php

namespace App\Events\Models\JobBatch;

use App\Events\AbstractLogicalEvent;
use App\Models\JobBatch;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class JobBatchCreated extends AbstractLogicalEvent implements ShouldBroadcast
{
    public function __construct(
        public JobBatch $batch
    ) {
    }

    public function broadcastOn()
    {
        return User::query()
            ->whereIn('email', config('auth.admin_emails'))
            ->get()
            ->map(fn (User $user) => new PrivateChannel('App.Models.User.'.$user->id))
            ->toArray();
    }

    public function broadcastAs()
    {
        return class_basename(static::class);
    }
}