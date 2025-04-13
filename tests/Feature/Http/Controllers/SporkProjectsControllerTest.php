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
        $response = $this->get('/-/projects');

        $response->assertStatus(200);
    }

    public function test_projects_create_route_is_accessible()
    {
        $response = $this->get('/-/projects/create');

        $response->assertStatus(200);
    }

    public function test_projects_show_route_is_accessible()
    {
        $project = Project::factory()->create();

        $response = $this->get("/-/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_projects_route_loads_expected_data()
    {
        $response = $this->get('/-/projects');

        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects')
        );
    }

    public function test_projects_show_route_loads_expected_data()
    {
        $project = Project::factory()->create();

        $response = $this->get("/-/projects/{$project->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project')
        );
    }
}
