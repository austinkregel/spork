<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Models\JobBatch\JobBatchUpdated;
use App\Jobs\Finance\LinkPrivacyTransactionsToPlaidJob;
use App\Jobs\Finance\SyncPlaidTransactionsJob;
use App\Jobs\Finance\SyncPrivacyCardsJob;
use App\Jobs\Finance\SyncPrivacyTransactionsJob;
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
            Log::info('FetchResourcesFromCredential: Batch cancelled, skipping', [
                'credential_id' => $this->credential->id,
                'credential_type' => $this->credential->type,
                'credential_service' => $this->credential->service,
            ]);

            return;
        }

        Log::info('FetchResourcesFromCredential: Processing credential', [
            'credential_id' => $this->credential->id,
            'credential_type' => $this->credential->type,
            'credential_service' => $this->credential->service,
            'user_id' => $this->credential->user_id,
        ]);

        $nextJob = match ($this->credential->type) {
            Credential::TYPE_REGISTRAR => new FetchRegistrarForCredential($this->credential),
            Credential::TYPE_DOMAIN => new FetchDomainsForCredential($this->credential),
            Credential::TYPE_SERVER => new FetchServersForCredential($this->credential),
            Credential::TYPE_FINANCE => match ($this->credential->service) {
                Credential::PLAID => new SyncPlaidTransactionsJob($this->credential),
                Credential::PRIVACY => null, // handled below (cards + transactions)
                default => null,
            },
            Credential::TYPE_EMAIL => new SyncMailboxIfCredentialsAreSet($this->credential),

            default => null,
        };

        if ($this->credential->type === Credential::TYPE_FINANCE && $this->credential->service === Credential::PLAID) {
            Log::info('FetchResourcesFromCredential: Routing to SyncPlaidTransactionsJob', [
                'credential_id' => $this->credential->id,
                'has_api_key' => ! empty($this->credential->api_key),
                'has_cursor' => ! empty($this->credential->settings['cursor'] ?? null),
            ]);
        }

        if ($this->credential->type === Credential::TYPE_FINANCE && $this->credential->service === Credential::PRIVACY) {
            $jobs = [
                new SyncPrivacyCardsJob($this->credential),
                new SyncPrivacyTransactionsJob($this->credential),
                new LinkPrivacyTransactionsToPlaidJob($this->credential),
            ];

            $batch = $this->batch();

            if ($batch) {
                $batch->add($jobs);

                $jobBatch = JobBatch::firstWhere('id', $batch->id);

                if ($jobBatch) {
                    broadcast(new JobBatchUpdated($jobBatch));
                }

                return;
            }

            $dispatcher->dispatch($jobs[0]);
            $dispatcher->dispatch($jobs[1]);

            return;
        }

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
