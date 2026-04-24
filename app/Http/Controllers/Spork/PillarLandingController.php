<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Contracts\Services\Navigation\NavigationRegistryContract;
use App\Navigation\Pillar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PillarLandingController
{
    public function __construct(
        private readonly NavigationRegistryContract $navigation_registry,
    ) {}

    public function __invoke(Request $request): Response
    {
        $name = $request->route()?->getName() ?? '';

        $pillar = match ($name) {
            'finance.landing' => Pillar::FINANCE,
            'communication.landing' => Pillar::COMMUNICATION,
            'feeds.landing' => Pillar::FEEDS,
            'projects.landing' => Pillar::PROJECTS,
            'automations.landing' => Pillar::AUTOMATIONS,
            'infrastructure.landing' => Pillar::INFRASTRUCTURE,
            default => null,
        };

        abort_if($pillar === null, 404);

        $page = match ($pillar) {
            Pillar::FINANCE => 'Pillars/Finance/Index',
            Pillar::COMMUNICATION => 'Pillars/Communication/Index',
            Pillar::FEEDS => 'Pillars/Feeds/Index',
            Pillar::PROJECTS => 'Pillars/Projects/Index',
            Pillar::AUTOMATIONS => 'Pillars/Automations/Index',
            Pillar::INFRASTRUCTURE => 'Pillars/Infrastructure/Index',
        };

        $user = $request->user();
        abort_if($user === null, 401);

        return Inertia::render($page, [
            'pillar' => [
                'slug' => $pillar->value,
                'label' => $pillar->label(),
                'icon' => $pillar->icon(),
            ],
            'summary_cards' => $this->navigation_registry->summaryCardsForPillar($pillar, $user),
            'sub_nav' => $this->navigation_registry->subNavForPillar($pillar, $user),
        ]);
    }
}
