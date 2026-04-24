<?php

declare(strict_types=1);

namespace App\Navigation\Pillars;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Navigation\PillarSummaryCard;

final class InfrastructurePillar implements PillarSubNavProviderContract
{
    public static function pillar(): Pillar
    {
        return Pillar::INFRASTRUCTURE;
    }

    public function subNav(User $user): array
    {
        $items = [
            new PillarSubNavItem(__('nav.infrastructure.servers'), route('infrastructure.servers.index'), 'ServerStackIcon'),
            new PillarSubNavItem(__('nav.infrastructure.add_server'), route('infrastructure.create'), 'PlusCircleIcon'),
            new PillarSubNavItem(__('nav.infrastructure.connect_host'), route('infrastructure.connect-host'), 'LinkIcon'),
        ];

        $domain = \App\Models\Domain::query()->first();
        if ($domain !== null) {
            $items[] = new PillarSubNavItem(__('nav.infrastructure.domains'), route('infrastructure.domains.show', $domain), 'GlobeAltIcon');
        }

        $items[] = new PillarSubNavItem(__('nav.infrastructure.cms_pages'), route('infrastructure.cms.pages.create'), 'DocumentTextIcon');

        return $items;
    }

    public function summaryCards(User $user): array
    {
        return [
            new PillarSummaryCard(__('nav.infrastructure.summary.servers'), '—', null, null, route('infrastructure.servers.index'), 'ServerStackIcon'),
        ];
    }
}
