<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Services\Feeds\FeedItem;
use App\Services\RssFeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PopulateExternalRssFeeds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ExternalRssFeed $feed;

    public function __construct(ExternalRssFeed $feed)
    {
        $this->feed = $feed;
    }

    public function handle(RssFeedService $service)
    {
        $rssFeed = $service->fetchRssFeed($this->feed->url);

        if ($rssFeed === null) {
            // TODO: dispatch some event or something to alert the system that this feed is dead.
            return;
        }

        /** @var FeedItem $item */
        foreach ($rssFeed->getData() as $item) {
            Article::fromFeedItem($this->feed, $item);
        }
    }
}
