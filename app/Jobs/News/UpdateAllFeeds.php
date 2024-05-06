<?php

declare(strict_types=1);

namespace App\Jobs\News;

use App\Events\Models\JobBatch\JobBatchCreated;
use App\Events\Models\JobBatch\JobBatchUpdated;
use App\Models\ExternalRssFeed;
use App\Models\JobBatch;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class UpdateAllFeeds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $jobs = ExternalRssFeed::query()
            ->select('id')
            ->get()
            ->map(fn (ExternalRssFeed $feed) => new \App\Jobs\News\UpdateFeed($feed));

        if ($jobs->isEmpty()) {
            return;
        }

        $batch = Bus::batch($jobs)
            ->allowFailures()
            ->name('Update All Feeds')
            ->then(function (Batch $batch) {
                broadcast(new JobBatchUpdated(JobBatch::firstWhere('id', $batch->id)));
            })->catch(function (Batch $batch, \Throwable $e) {
                broadcast(new JobBatchUpdated(JobBatch::firstWhere('id', $batch->id)));
            })->finally(function (Batch $batch) {
                broadcast(new JobBatchUpdated(JobBatch::firstWhere('id', $batch->id)));
            })
            ->onQueue('secondary')
            ->dispatch();

        broadcast(new JobBatchCreated(JobBatch::firstWhere('id', $batch->id)));
    }
}
