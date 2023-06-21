<?php

declare(strict_types=1);

namespace App\Jobs\News;

use App\Models\Article;
use App\Models\FeatureList;
use App\Services\News\Feeds\AbstractFeed;
use App\Services\News\Feeds\FeedItem;
use App\Services\News\RssFeedService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateFeed implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected FeatureList $feed;

    public function __construct(FeatureList $feed)
    {
        $this->feed = $feed;
    }

    public function handle(RssFeedService $service)
    {
        /** @var AbstractFeed $rssFeed */
        $rssFeed = $service->fetchRssFeed($this->feed->settings['url'] ?? 'https://fake.tools');

        if (empty($rssFeed)) {
            dd($rssFeed, $this->feed);
        }
        /** @var FeedItem $feedItem */
        try {
            info("Fetching feed from {$this->feed->settings['url']}");
            foreach ($rssFeed->getData() as $feedItem) {
                if (
                    $this->feed->articles()
                        ->where('external_guid', $feedItem->getUuidIfExists())
                        ->exists()
                ) {
                    break;
                }

                if (str_contains($feedItem->title, '#shorts')) {
                    $item = Article::firstWhere('external_guid', $feedItem->getExternalId());

                    if (! empty($item)) {
                        $item->delete();
                    }

                    continue;
                }

                Article::fromFeedItem($this->feed, $feedItem);
            }
        } catch (\Throwable $e) {
            info($e->getMessage(), ['error' => $e, 'feed' => $rssFeed]);
            dd($e);
        }

//        $this->feed->name = $rssFeed->getName();

        if (! empty($rssFeed->getPhoto())) {
            $this->feed->settings = array_merge(['profile_photo_path' => $rssFeed->getPhoto()], $this->feed->settings);
        }

        if (! empty($rssFeed->getLastModified())) {
            $this->feed->settings = array_merge(['last_modified' => $rssFeed->getLastModified()], $this->feed->settings);
        }

        if (! empty($rssFeed->getEtag())) {
            $this->feed->settings = array_merge(['etag' => $rssFeed->getEtag()], $this->feed->settings);
        }

        $lastArticle = $this->feed->articles()
            ->select('last_modified')
            ->orderByDesc('last_modified')
            ->limit(1)
            ->pluck('last_modified')
            ->first();

        if (! empty($lastArticle)) {
            $lastArticle = Carbon::parse($lastArticle);
            $this->feed->settings = array_merge(['last_published_at' => $lastArticle], $this->feed->settings);
            $this->feed->updated_at = $lastArticle;
        }

        if ($this->feed->isDirty()) {
            $this->feed->save();
        }
    }
}
