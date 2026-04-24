<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Contracts\Dav\DavCollectionSource;
use App\Services\Dav\Backends\EloquentSourceRegistry;
use App\Services\Dav\Sources\EventCalDavSource;
use App\Services\Dav\Sources\PersonCardDavSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DavCollectionDiscoveryTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_registry_discovers_built_in_sources(): void
    {
        $registry = app(EloquentSourceRegistry::class);
        $registry->discover();

        $classes = $registry->all()->map(fn (DavCollectionSource $source) => $source::class)->all();

        $this->assertContains(PersonCardDavSource::class, $classes);
        $this->assertContains(EventCalDavSource::class, $classes);
    }

    public function test_registry_groups_sources_by_collection_type(): void
    {
        $registry = app(EloquentSourceRegistry::class);

        $addressbooks = $registry->ofType(DavCollectionSource::TYPE_ADDRESSBOOK)
            ->map(fn (DavCollectionSource $s) => $s::class)->all();
        $calendars = $registry->ofType(DavCollectionSource::TYPE_CALENDAR)
            ->map(fn (DavCollectionSource $s) => $s::class)->all();

        $this->assertContains(PersonCardDavSource::class, $addressbooks);
        $this->assertContains(EventCalDavSource::class, $calendars);
        $this->assertNotContains(EventCalDavSource::class, $addressbooks);
    }
}
