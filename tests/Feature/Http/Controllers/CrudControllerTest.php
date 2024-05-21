<?php

declare(strict_types=1);

namespace Feature\Http\Controllers;

use App;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ?App\Models\User $user = null;

    public function actingAsUser(): User
    {
        if (! Role::firstWhere('name', 'developer')) {
            Role::create(['name' => 'developer']);
        }

        $this->actingAs($this->user = App\Models\User::factory()->withPersonalTeam()->create());
        $this->user->assignRole('developer');

        return $this->user;
    }

    public function testBasicTestSuccess(): void
    {
        $this->actingAsUser();
        Project::factory()->create([
            'name' => 'github@austinkregel.com',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $response = $this->get('http://spork.localhost/api/crud/projects');

        $response->assertStatus(200);
    }

    public function testCreateTestSuccess(): void
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

    public function testUpdateTestSuccess(): void
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

    public function testUpdatePatchTestSuccess(): void
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

    public function testDeleteTestSuccess(): void
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

    public function testShowTestSuccess(): void
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
