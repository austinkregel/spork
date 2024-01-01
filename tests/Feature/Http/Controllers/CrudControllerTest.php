<?php

declare(strict_types=1);

namespace Feature\Http\Controllers;

use App;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ?App\Models\User $user = null;

    public function actingAsUser()
    {
        return $this->actingAs($this->user = App\Models\User::factory()->withPersonalTeam()->create());
    }

    public function testBasicTestWithoutBypassSuccess()
    {
        $this->actingAsUser();
        Project::factory()->create([
            'name' => 'github@austinkregel.com',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $response = $this->get('http://spork.localhost/api/crud/projects');

        $response->assertStatus(200);
    }

    public function testCreateTestWithoutBypassSuccess()
    {
        $this->actingAsUser();
        Project::factory()->create([
            'name' => 'github@austinkregel.com',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $response = $this
            ->post('http://spork.localhost/api/crud/projects', [
                'name' => 'Austin',
                'team_id' => $this->user->currentTeam->id,
            ]);

        $response->assertStatus(201);
    }

    public function testUpdateTestWithoutBypassSuccess()
    {
        $this->actingAsUser();
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $response = $this
            ->put('http://spork.localhost/api/crud/projects/'.$project->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function testUpdatePatchTestWithoutBypassSuccess()
    {
        $this->actingAsUser();
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $response = $this
            ->patch('http://spork.localhost/api/crud/projects/'.$project->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function testDeleteTestWithoutBypassSuccess()
    {
        $this->actingAsUser();
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);
        $project2 = Project::factory()->create();

        $response = $this
            ->delete('http://spork.localhost/api/crud/projects/'.$project2->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('projects', $project2->toArray());
    }

    public function testShowTestWithoutBypassSuccess()
    {
        $this->actingAsUser();
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->user->permissions()->create([
            'name' => 'view_project',
            'guard_name' => 'verified',
        ]);

        $response = $this
            ->get('http://spork.localhost/api/crud/projects/'.$project->id);

        $response->assertStatus(200);

        $response->assertJson($project->toArray());
    }
}
