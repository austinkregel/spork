<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkResearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_research_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/research');

        $response->assertStatus(200);
    }

    public function test_research_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/research');

        $response->assertInertia(fn ($page) => $page
            ->component('Research/Dashboard')
            ->has('research')
        );
    }

    public function test_research_show_route_is_accessible()
    {
        $research = \App\Models\Research::factory()->create([
            'topic' => 'Test Research',
            'notes' => '',
            'sources' => [],
        ]);

        $response = $this->actingAsUser()->get("http://spork.localhost/-/research/{$research->id}");

        $response->assertStatus(200);
    }

    public function test_research_show_route_loads_expected_data()
    {
        $research = \App\Models\Research::factory()->create([
            'topic' => 'Test Research',
            'notes' => '',
            'sources' => [],
        ]);

        $response = $this->actingAsUser()->get("http://spork.localhost/-/research/{$research->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Research/Topic')
            ->has('topic')
        );
    }
}
