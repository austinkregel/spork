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

    protected $user = null;

    public function actingAsUser()
    {
        return $this->actingAs($this->user = App\Models\User::factory()->create());
    }

    public function testBasicTestWithoutBypassSuccess()
    {
        Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);
        $response = $this->actingAsUser()->get('http://spork.localhost/api/crud/projects');

        $response->assertStatus(200);
    }

    public function testCreateTestWithoutBypassSuccess()
    {
        Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);
        $response = $this
            ->actingAsUser()
            ->post('http://spork.localhost/api/crud/projects', [
                'name' => 'Austin',
            ]);

        $response->assertStatus(201);
    }

    public function testUpdateTestWithoutBypassSuccess()
    {
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $response = $this->actingAsUser()
            ->put('http://spork.localhost/api/crud/projects/'.$project->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function testUpdatePatchTestWithoutBypassSuccess()
    {
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $response = $this->actingAsUser()
            ->patch('http://spork.localhost/api/crud/projects/'.$project->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function testDeleteTestWithoutBypassSuccess()
    {
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);
        $project2 = Project::factory()->create();

        $response = $this->actingAsUser()
            ->delete('http://spork.localhost/api/crud/projects/'.$project2->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('projects', $project2->toArray());
    }

    public function testShowTestWithoutBypassSuccess()
    {
        $project = Project::factory()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $response = $this->actingAsUser()
            ->get('http://spork.localhost/api/crud/projects/'.$project->id);

        $response->assertStatus(200);

        $response->assertJson($project->toArray());
    }
}
