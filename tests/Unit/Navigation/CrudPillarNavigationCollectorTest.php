<?php

declare(strict_types=1);

namespace Tests\Unit\Navigation;

use App\Navigation\Pillar;
use App\Services\Navigation\CrudPillarNavigationCollector;
use Tests\TestCase;

final class CrudPillarNavigationCollectorTest extends TestCase
{
    public function test_discover_groups_opt_in_crud_models_by_pillar(): void
    {
        $collector = CrudPillarNavigationCollector::discover();

        $infra = $collector->manageItemsForPillar(Pillar::INFRASTRUCTURE);
        $this->assertNotEmpty($infra);
        $this->assertTrue(collect($infra)->contains(fn ($i) => str_contains($i->href, 'servers')));

        $finance = $collector->manageItemsForPillar(Pillar::FINANCE);
        $this->assertTrue(collect($finance)->contains(fn ($i) => str_contains($i->href, 'budgets')));

        $autos = $collector->manageItemsForPillar(Pillar::AUTOMATIONS);
        $this->assertTrue(collect($autos)->contains(fn ($i) => str_contains($i->href, 'automations')));
    }
}
