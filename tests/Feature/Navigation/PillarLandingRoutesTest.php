<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PillarLandingRoutesTest extends TestCase
{
    use RefreshDatabase;

    public static function pillarLandings(): array
    {
        return [
            'finance' => ['/-/finance', 'Pillars/Finance/Index'],
            'communication' => ['/-/communication', 'Pillars/Communication/Index'],
            'feeds' => ['/-/feeds', 'Pillars/Feeds/Index'],
            'projects' => ['/-/projects', 'Pillars/Projects/Index'],
            'automations' => ['/-/automations', 'Pillars/Automations/Index'],
            'infrastructure' => ['/-/infrastructure', 'Pillars/Infrastructure/Index'],
        ];
    }

    /**
     * @dataProvider pillarLandings
     */
    public function test_each_pillar_landing_loads_for_authenticated_user(string $path, string $component): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('http://spork.localhost'.$path);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component($component)
            ->has('pillar.slug')
            ->has('pillar.label')
            ->has('summary_cards')
            ->has('sub_nav')
        );
    }

    public function test_pillar_landings_require_authentication(): void
    {
        $response = $this->get('http://spork.localhost/-/finance');

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    public function test_projects_list_is_separate_from_landing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('http://spork.localhost/-/projects')->assertOk();
        $this->actingAs($user)->get('http://spork.localhost/-/projects/list')->assertOk();
    }
}
