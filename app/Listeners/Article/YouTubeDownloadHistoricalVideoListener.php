<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Models\Article\ArticleCreated;

class YouTubeDownloadHistoricalVideoListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ArticleCreated $event): void
    {
        $hasItems = $event->model->externalRssFeed->tagsWithType('auto')->count() > 0;

        if ($hasItems > 0) {
            return;
        }

        // We want to
    }
}
