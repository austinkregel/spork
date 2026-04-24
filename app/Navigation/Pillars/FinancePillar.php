<?php

declare(strict_types=1);

namespace App\Navigation\Pillars;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Navigation\PillarSummaryCard;

final class FinancePillar implements PillarSubNavProviderContract
{
    public static function pillar(): Pillar
    {
        return Pillar::FINANCE;
    }

    public function subNav(User $user): array
    {
        return [
            new PillarSubNavItem(__('nav.finance.overview'), route('finance.banking.overview'), 'HomeIcon'),
            new PillarSubNavItem(__('nav.finance.accounts'), route('finance.banking.accounts'), 'BuildingLibraryIcon'),
            new PillarSubNavItem(__('nav.finance.budgets'), route('finance.banking.budgets'), 'ChartPieIcon'),
            new PillarSubNavItem(__('nav.finance.transactions'), route('finance.banking.transactions'), 'ArrowsRightLeftIcon'),
            new PillarSubNavItem(__('nav.finance.privacy'), route('finance.banking.privacy'), 'ShieldCheckIcon'),
            new PillarSubNavItem(__('nav.finance.settings'), route('finance.banking.settings'), 'Cog6ToothIcon'),
        ];
    }

    public function summaryCards(User $user): array
    {
        return [
            new PillarSummaryCard(__('nav.finance.summary.accounts'), '—', null, null, route('finance.banking.accounts'), 'BuildingLibraryIcon'),
            new PillarSummaryCard(__('nav.finance.summary.transactions'), '—', null, null, route('finance.banking.transactions'), 'ArrowsRightLeftIcon'),
        ];
    }
}
