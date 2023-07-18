<?php

declare(strict_types=1);

namespace App\Jobs\News;

use App\Models\ExternalRssFeed;
use App\Services\News\Feeds\RssFeed;
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

        Bus::batch($jobs)->allowFailures()->name('Update All Feeds')->dispatch();
    }
}
