<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\News\PopulateExternalRssFeeds;
use App\Models\ExternalRssFeed;
use App\Services\News\RssFeedService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PopulateExternalRssFeedsTest extends TestCase
{
    public function test_logs_warning_when_feed_is_null(): void
    {
        Log::spy();

        $feed = new ExternalRssFeed([
            'id' => 1,
            'url' => 'https://example.com/rss',
        ]);

        $job = new PopulateExternalRssFeeds($feed);

        $service = Mockery::mock(RssFeedService::class);
        $service->shouldReceive('fetchRssFeed')
            ->once()
            ->with('https://example.com/rss')
            ->andReturn(null);

        $job->handle($service);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('External RSS feed is unavailable', [
                'feed_id' => $feed->id,
                'url' => $feed->url,
            ]);
    }
}


