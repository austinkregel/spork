<?php

declare(strict_types=1);

namespace App\Navigation\Pillars;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Navigation\PillarSummaryCard;

final class AutomationsPillar implements PillarSubNavProviderContract
{
    public static function pillar(): Pillar
    {
        return Pillar::AUTOMATIONS;
    }

    public function subNav(User $user): array
    {
        return [
            new PillarSubNavItem(__('nav.automations.workspace'), route('automations.workspace'), 'BoltIcon'),
            new PillarSubNavItem(__('nav.automations.tags'), route('automations.tags'), 'TagIcon'),
            new PillarSubNavItem(__('nav.automations.operations'), route('automations.operations.index'), 'CommandLineIcon'),
        ];
    }

    public function summaryCards(User $user): array
    {
        return [
            new PillarSummaryCard(__('nav.automations.summary.automations'), '—', null, null, route('automations.automations.index'), 'BoltIcon'),
        ];
    }
}
