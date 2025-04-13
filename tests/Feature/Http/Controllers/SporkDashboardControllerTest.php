<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_route_is_accessible()
    {
        $response = $this->get('/-/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_route_loads_expected_data()
    {
        $response = $this->get('/-/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('user')
            ->has('notifications')
        );
    }
}
