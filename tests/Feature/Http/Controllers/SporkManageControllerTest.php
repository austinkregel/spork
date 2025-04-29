<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkManageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_slug_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/manage/projects');

        $response->assertStatus(200);
    }

    public function test_manage_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/manage');

        $response->assertStatus(200);
    }

    public function test_manage_slug_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/manage/projects');

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Manage/List')
        );
    }

    public function test_manage_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/manage');

        $response->assertInertia(fn ($page) => $page
            ->component('Manage/Index')
            ->has('activity')
        );
    }
}
