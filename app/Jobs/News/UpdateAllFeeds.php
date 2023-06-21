<?php

declare(strict_types=1);

namespace App\Jobs\News;

use App\Events\BatchFinishedRunningEvent;
use App\Models\FeatureList;
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
        $jobs = FeatureList::query()
            ->where('feature', 'rss')
            ->get()
            ->map(fn ($feed) => new UpdateFeed($feed));

        Bus::batch($jobs)
            ->allowFailures()
            ->name('Update All Feeds')
            ->finally(function () {
                broadcast(new BatchFinishedRunningEvent(...func_get_args()));
            })
            ->dispatch();
    }
}
