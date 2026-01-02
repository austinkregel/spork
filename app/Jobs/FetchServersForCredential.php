<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Servers\DigitalOceanSyncJob;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchServersForCredential implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Credential $credential,
        public ?User $user = null,
    ) {
        $this->user = $user ?? auth()->user();
    }

    /**
     * Execute the job.
     */
    public function handle(Dispatcher $dispatcher): void
    {

        if ($this->batch()?->cancelled()) {
            return;
        }
        if ($this->credential->type !== Credential::TYPE_SERVER) {
            return;
        }

        $nextJob = match ($this->credential->service) {
            Credential::DIGITAL_OCEAN => new DigitalOceanSyncJob($this->credential, $this->user),
            default => null,
        };

        if (! $nextJob) {
            Log::error('Unsupported credential service for FetchServersForCredential', [
                'credential_id' => $this->credential->id,
                'credential_type' => $this->credential->type,
                'credential_service' => $this->credential->service,
                'batch_id' => $this->batch()?->id,
            ]);

            return;
        }

        $batch = $this->batch();

        if ($batch) {
            $batch->add([$nextJob]);

            return;
        }

        $dispatcher->dispatch($nextJob);
    }
}
