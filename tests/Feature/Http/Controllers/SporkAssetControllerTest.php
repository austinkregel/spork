<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkAssetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_route_is_accessible()
    {
        $response = $this->get('/-/assets');

        $response->assertStatus(200);
    }

    public function test_labels_route_is_accessible()
    {
        $response = $this->get('/-/labels');

        $response->assertStatus(200);
    }

    public function test_assets_route_loads_expected_data()
    {
        $response = $this->get('/-/assets');

        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Index')
            ->has('assets')
        );
    }

    public function test_labels_route_loads_expected_data()
    {
        $response = $this->get('/-/labels');

        $response->assertInertia(fn ($page) => $page
            ->component('Labels/Index')
            ->has('labels')
        );
    }
}
