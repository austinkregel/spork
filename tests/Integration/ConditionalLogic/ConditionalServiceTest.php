<?php

declare(strict_types=1);

namespace Tests\Integration\ConditionalLogic;

use App\Models\User;
use App\Services\ConditionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testWeShowLoginRouteWhenUnauthenticated()
    {
        $service = new ConditionService();

        $navigation = $service->navigation();

        $this->assertCount(1, $navigation);

        $items = $navigation->toArray();

        $this->assertCount(1, $items);

        $login = $items[0];

        $this->assertSame('Login', $login['name']);
        $this->assertSame('/login', $login['href']);
    }

    public function testWeShowDashboardRoutesWhenLoggedIn()
    {
        $service = new ConditionService();
        $user = User::factory()->create();
        auth()->login($user);

        $navigation = $service->navigation();

        $items = $navigation->toArray();

        //        $this->assertCount(8, $items);

        $this->assertSame([
            0 => 'Dashboard',
            1 => 'Projects',
            2 => 'Banking',
            3 => 'CRUD',
            5 => 'Tags',
            6 => 'Email',
            7 => 'Settings',
        ], array_map(fn ($item) => $item['name'], $items));
    }

    public function testWeShowLogicRouteWhenLoggedInAndLocal()
    {
        config(['app.env' => 'local']);

        $service = new ConditionService();
        $user = User::factory()->create();
        auth()->login($user);

        $navigation = $service->navigation();

        $items = $navigation->toArray();

        $this->assertCount(8, $items);

        $this->assertSame([
            0 => 'Dashboard',
            1 => 'Projects',
            2 => 'Banking',
            3 => 'CRUD',
            4 => 'Logic',
            5 => 'Tags',
            6 => 'Email',
            7 => 'Settings',
        ], array_map(fn ($item) => $item['name'], $items));
    }
}
