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
use Illuminate\Support\Facades\Log;
use Throwable;

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

    public function failed(Throwable $e): void
    {
        Log::error('Sync job failed', [
            'job_class' => static::class,
            'job_id' => $this->job?->getJobId(),
            'job_attempts' => $this->job?->attempts(),
            'job_queue' => $this->job?->getQueue(),
            'job_connection' => $this->job?->getConnectionName(),
            'batch_id' => $this->batch()?->id,
            'credential_id' => $this->credential->id ?? null,
            'credential_type' => $this->credential->type ?? null,
            'credential_service' => $this->credential->service ?? null,
            'user_id' => $this->user?->id,
            'exception_class' => $e::class,
            'exception_message' => $e->getMessage(),
        ]);
    }
}
