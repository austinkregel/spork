<?php

declare(strict_types=1);

namespace App\Navigation\Pillars;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Navigation\PillarSummaryCard;

final class FeedsPillar implements PillarSubNavProviderContract
{
    public static function pillar(): Pillar
    {
        return Pillar::FEEDS;
    }

    public function subNav(User $user): array
    {
        return [
            new PillarSubNavItem(__('nav.feeds.social'), route('feeds.rss-feeds.index'), 'RssIcon'),
        ];
    }

    public function summaryCards(User $user): array
    {
        return [
            new PillarSummaryCard(__('nav.feeds.summary.feeds'), '—', null, null, route('feeds.rss-feeds.index'), 'RssIcon'),
        ];
    }
}
