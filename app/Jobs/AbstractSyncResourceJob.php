<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\ModelQuery;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class AbstractSyncResourceJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ModelQuery $model;

    public function __construct(
        public Credential $credential,
        public ?User $user = null
    ) {
        $this->user = $user ?? auth()->user();
    }

    abstract public function sync(): void;
}
