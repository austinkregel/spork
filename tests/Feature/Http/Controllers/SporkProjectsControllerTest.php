<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Project;

class SporkProjectsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/projects');

        $response->assertStatus(200);
    }

    public function test_projects_create_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/projects/create');

        $response->assertStatus(200);
    }

    public function test_projects_show_route_is_accessible()
    {
        $project = Project::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_projects_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/projects');

        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('data')
        );
    }

    public function test_projects_show_route_loads_expected_data()
    {
        $project = Project::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/projects/{$project->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Project')
            ->has('project')
        );
    }
}
