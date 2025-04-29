<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkPagesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_create_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/pages/create');

        $response->assertStatus(200);
    }

    public function test_pages_create_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/pages/create');

        $response->assertInertia(fn ($page) => $page
            ->component('Pages/BlueprintEditor')
        );
    }
}
