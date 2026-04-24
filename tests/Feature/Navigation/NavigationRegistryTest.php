<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use App\Contracts\Services\Navigation\NavigationRegistryContract;
use App\Models\User;
use App\Navigation\Pillar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NavigationRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_six_pillars_are_registered(): void
    {
        $registry = app(NavigationRegistryContract::class);

        $slugs = array_map(
            static fn (array $entry) => $entry['slug'],
            $registry->pillarsPayload(User::factory()->create()),
        );

        sort($slugs);
        $expected = array_map(static fn (Pillar $p) => $p->value, Pillar::cases());
        sort($expected);

        $this->assertSame($expected, $slugs);
    }

    public function test_provider_for_returns_a_provider_per_pillar(): void
    {
        $registry = app(NavigationRegistryContract::class);

        foreach (Pillar::cases() as $pillar) {
            $this->assertNotNull(
                $registry->providerFor($pillar),
                sprintf('Expected a provider for the %s pillar.', $pillar->value),
            );
        }
    }

    public function test_pillars_payload_includes_label_icon_and_href(): void
    {
        $registry = app(NavigationRegistryContract::class);

        $entry = $registry->pillarsPayload(User::factory()->create())[0];

        $this->assertArrayHasKey('slug', $entry);
        $this->assertArrayHasKey('label', $entry);
        $this->assertArrayHasKey('icon', $entry);
        $this->assertArrayHasKey('href', $entry);
        $this->assertNotEmpty($entry['href']);
    }

    public function test_sub_nav_for_pillar_returns_array_for_each_pillar(): void
    {
        $registry = app(NavigationRegistryContract::class);
        $user = User::factory()->create();

        foreach (Pillar::cases() as $pillar) {
            $items = $registry->subNavForPillar($pillar, $user);
            $this->assertIsArray($items);
        }
    }

    public function test_current_pillar_parses_known_paths(): void
    {
        $registry = app(NavigationRegistryContract::class);

        $this->assertSame(Pillar::FINANCE, $registry->currentPillar('/-/finance'));
        $this->assertSame(Pillar::PROJECTS, $registry->currentPillar('-/projects/list'));
        $this->assertNull($registry->currentPillar('/'));
        $this->assertNull($registry->currentPillar(null));
        $this->assertNull($registry->currentPillar('/-/not-a-pillar'));
    }
}
