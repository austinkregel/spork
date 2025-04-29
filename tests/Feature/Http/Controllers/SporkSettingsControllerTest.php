<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/settings');

        $response->assertStatus(200);
    }

    public function test_settings_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/settings');

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('config')
            ->has('settings')
        );
    }
}
