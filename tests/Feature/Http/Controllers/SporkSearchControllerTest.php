<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_route_is_accessible()
    {
        $response = $this->get('/-/search');

        $response->assertStatus(200);
    }

    public function test_search_route_loads_expected_data()
    {
        $response = $this->get('/-/search');

        $response->assertInertia(fn ($page) => $page
            ->component('Search')
            ->has('results')
        );
    }
}
