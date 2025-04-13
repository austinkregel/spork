<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkManageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_slug_route_is_accessible()
    {
        $response = $this->get('/-/manage/{slug}');

        $response->assertStatus(200);
    }

    public function test_manage_route_is_accessible()
    {
        $response = $this->get('/-/manage');

        $response->assertStatus(200);
    }

    public function test_manage_slug_route_loads_expected_data()
    {
        $response = $this->get('/-/manage/{slug}');

        $response->assertInertia(fn ($page) => $page
            ->component('Manage/Show')
            ->has('slug')
            ->has('data')
        );
    }

    public function test_manage_route_loads_expected_data()
    {
        $response = $this->get('/-/manage');

        $response->assertInertia(fn ($page) => $page
            ->component('Manage/Index')
            ->has('data')
        );
    }
}
