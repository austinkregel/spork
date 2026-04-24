<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Domain;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkProjectsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/projects/list');

        $response->assertStatus(200);
    }

    public function test_projects_create_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/projects/create');

        $response->assertStatus(200);
    }

    public function test_projects_create_route_includes_templates_and_registry()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/projects/create');

        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
            ->has('project_templates')
        );
    }

    public function test_projects_show_route_is_accessible()
    {
        $project = Project::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_projects_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/projects/list');

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

    public function test_can_attach_allowed_resource_type_to_project()
    {
        $project = Project::factory()->create();
        $domain = Domain::factory()->create();

        $response = $this->actingAsUser()->post("http://spork.localhost/-/projects/{$project->id}/attach", [
            'resource_type' => \App\Models\Domain::class,
            'resource_id' => $domain->id,
        ]);

        $response->assertStatus(204);

        $this->assertDatabaseHas('project_resources', [
            'project_id' => $project->id,
            'resource_type' => \App\Models\Domain::class,
            'resource_id' => $domain->id,
        ]);
    }

    public function test_cannot_attach_disallowed_resource_type_to_project()
    {
        $project = Project::factory()->create();

        $this->actingAsUser();

        $response = $this->postJson("http://spork.localhost/-/projects/{$project->id}/attach", [
            'resource_type' => \App\Models\User::class,
            'resource_id' => $this->user->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['resource_type']);
    }

    public function test_project_store_can_persist_template_settings_and_attach_resources()
    {
        $this->actingAsUser();
        $user = $this->user;
        $domain = Domain::factory()->create();

        $response = $this->post('http://spork.localhost/-/projects', [
            'name' => 'My Project',
            'user_id' => $user->id,
            'settings' => [
                'template' => 'research_hub',
            ],
            'attachments' => [
                [
                    'resource_type' => \App\Models\Domain::class,
                    'resource_id' => $domain->id,
                ],
            ],
        ]);

        $response->assertRedirect();

        $project = Project::query()->where('name', 'My Project')->firstOrFail();
        $this->assertSame('content_research', $project->settings['template'] ?? null);

        $this->assertDatabaseHas('project_resources', [
            'project_id' => $project->id,
            'resource_type' => \App\Models\Domain::class,
            'resource_id' => $domain->id,
        ]);
    }

    public function test_can_quick_create_research_and_attach_to_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAsUser()->postJson("http://spork.localhost/api/projects/{$project->id}/research", [
            'topic' => 'Investigate ACME',
            'notes' => 'Initial notes',
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['topic' => 'Investigate ACME']);

        $researchId = (int) $response->json('id');

        $this->assertDatabaseHas('research', [
            'id' => $researchId,
            'topic' => 'Investigate ACME',
        ]);

        $this->assertDatabaseHas('project_resources', [
            'project_id' => $project->id,
            'resource_type' => \App\Models\Research::class,
            'resource_id' => $researchId,
        ]);
    }
}
