<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Models\JobBatch\JobBatchCreated;
use App\Events\Models\JobBatch\JobBatchUpdated;
use App\Models\Credential;
use App\Models\JobBatch;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FetchResourcesFromCredentials implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
    ) {}

    /**
     * Execute the job.
     */
    public function handle(QueueingDispatcher $dispatcher): void
    {
        $credentials = Credential::query()
            // Matrix credentials are handled in their own way.
            ->whereNotIn('service', ['matrix', 'ssh'])
            ->get();

        Log::info('FetchResourcesFromCredentials: Starting batch job', [
            'total_credentials' => $credentials->count(),
            'plaid_credentials' => $credentials->where('type', Credential::TYPE_FINANCE)->where('service', Credential::PLAID)->count(),
        ]);

        $jobs = $credentials->groupBy('user_id')
            ->map(fn (Collection $group) => $group->map(fn ($credential) => new FetchResourcesFromCredential($credential))->toArray())
            ->toArray();

        if (empty($jobs)) {
            Log::warning('FetchResourcesFromCredentials: No credentials found to process');

            return;
        }

        $batch = $dispatcher->batch($jobs)
            ->name('Updatch Resources From Credentials')
            ->allowFailures()
            ->then(function (Batch $batch) {
                broadcast(new JobBatchUpdated(JobBatch::firstWhere('id', $batch->id)));
            })->catch(function (Batch $batch, \Throwable $e) {
                broadcast(new JobBatchUpdated(JobBatch::firstWhere('id', $batch->id)));
            })->finally(function (Batch $batch) {
                broadcast(new JobBatchUpdated(JobBatch::firstWhere('id', $batch->id)));
            })
            ->dispatch();

        broadcast(new JobBatchCreated(JobBatch::firstWhere('id', $batch->id)));
    }
}
