<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_test_success(): void
    {
        $this->actingAsUser();
        $project = $this->user->projects()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $this->user->permissions()->create([
            'name' => 'view_any_project',
            'guard_name' => 'web',
        ]);
        $this->user->assignRole('developer');
        $response = $this->get('http://spork.localhost/api/crud/projects');

        $response->assertStatus(200);
    }

    public function test_create_test_success(): void
    {
        $this->actingAsUser();
        $this->user->projects()->create([
            'name' => 'github@austinkregel.com',
        ]);
        $this->user->permissions()->create([
            'name' => 'create_project',
            'guard_name' => 'web',
        ]);
        $response = $this
            ->post('http://spork.localhost/api/crud/projects', [
                'name' => 'Austin',
            ]);

        $response->assertStatus(201);
    }

    public function test_update_test_success(): void
    {
        $this->actingAsUser();
        $project = $this->user->projects()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $this->user->permissions()->create([
            'name' => 'update_project.'.$project->id,
            'guard_name' => 'web',
        ]);
        $response = $this
            ->put('http://spork.localhost/api/crud/projects/'.$project->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function test_update_patch_test_success(): void
    {
        $this->actingAsUser();
        $project = $this->user->projects()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $this->user->permissions()->create([
            'name' => 'update_project.'.$project->id,
            'guard_name' => 'web',
        ]);
        $response = $this
            ->patch('http://spork.localhost/api/crud/projects/'.$project->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function test_delete_test_success(): void
    {
        $this->actingAsUser();
        $project = $this->user->projects()->create([
            'name' => 'github@austinkregel.com',
        ]);
        $project2 = $this->user->projects()->create([
            'name' => 'project 2',
        ]);
        $this->user->permissions()->create([
            'name' => 'delete_project.'.$project2->id,
            'guard_name' => 'web',
        ]);
        $response = $this
            ->delete('http://spork.localhost/api/crud/projects/'.$project2->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('projects', $project2->toArray());
    }

    public function test_show_test_success(): void
    {
        $this->actingAsUser();
        $project = $this->user->projects()->create([
            'name' => 'github@austinkregel.com',
        ]);

        $this->user->permissions()->create([
            'name' => 'view_project.'.$project->id,
            'guard_name' => 'web',
        ]);

        $response = $this
            ->get('http://spork.localhost/api/crud/projects/'.$project->id);

        $response->assertStatus(200);

        $response->assertJson($project->toArray());
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}
