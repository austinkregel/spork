<?php

namespace App\Contracts\Services\News;

use App\Services\News\Feeds\AbstractFeed;

interface RssServiceContract
{
    public function fetchRssFeed(string $url): ?AbstractFeed;
}
