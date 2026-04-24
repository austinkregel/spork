<?php

declare(strict_types=1);

namespace App\Contracts\Navigation\Pillars;

use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Navigation\PillarSummaryCard;

interface PillarSubNavProviderContract
{
    public static function pillar(): Pillar;

    /**
     * @return list<PillarSubNavItem>
     */
    public function subNav(User $user): array;

    /**
     * @return list<PillarSummaryCard>
     */
    public function summaryCards(User $user): array;
}
