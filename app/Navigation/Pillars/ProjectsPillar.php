<?php

declare(strict_types=1);

namespace App\Navigation\Pillars;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Navigation\PillarSummaryCard;

final class ProjectsPillar implements PillarSubNavProviderContract
{
    public static function pillar(): Pillar
    {
        return Pillar::PROJECTS;
    }

    public function subNav(User $user): array
    {
        return [
            new PillarSubNavItem(__('nav.projects.all'), route('projects.index'), 'RectangleStackIcon'),
            new PillarSubNavItem(__('nav.projects.new'), route('projects.create'), 'PlusIcon'),
            new PillarSubNavItem(__('nav.projects.research'), route('projects.research.index'), 'BeakerIcon'),
            new PillarSubNavItem(__('nav.projects.assets'), route('projects.assets.index'), 'PhotoIcon'),
        ];
    }

    public function summaryCards(User $user): array
    {
        return [
            new PillarSummaryCard(__('nav.projects.summary.total'), (string) $user->personalProjects()->count(), null, null, route('projects.index'), 'ClipboardDocumentListIcon'),
        ];
    }
}
