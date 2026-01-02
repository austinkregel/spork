<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Models\JobBatch\JobBatchUpdated;
use App\Jobs\Finance\SyncPlaidTransactionsJob;
use App\Models\Credential;
use App\Models\JobBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchResourcesFromCredential implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Credential $credential
    ) {}

    /**
     * Execute the job.
     */
    public function handle(Dispatcher $dispatcher): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $nextJob = match ($this->credential->type) {
            Credential::TYPE_REGISTRAR => new FetchRegistrarForCredential($this->credential),
            Credential::TYPE_DOMAIN => new FetchDomainsForCredential($this->credential),
            Credential::TYPE_SERVER => new FetchServersForCredential($this->credential),
            Credential::TYPE_FINANCE => new SyncPlaidTransactionsJob($this->credential, now()->subWeek(), now(), false),
            Credential::TYPE_EMAIL => new SyncMailboxIfCredentialsAreSet($this->credential),
        
            default => null,
        };

        if (! $nextJob) {
            Log::error('Unsupported credential type for FetchResourcesFromCredential', [
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

            $jobBatch = JobBatch::firstWhere('id', $batch->id);

            if ($jobBatch) {
                broadcast(new JobBatchUpdated($jobBatch));
            }
        } else {
            $dispatcher->dispatch($nextJob);
        }
    }
}
