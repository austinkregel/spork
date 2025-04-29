<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkAssetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/assets');

        $response->assertStatus(200);
    }
    public function test_assets_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/assets');

        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Index')
            ->has('assets')
        );
    }
}
