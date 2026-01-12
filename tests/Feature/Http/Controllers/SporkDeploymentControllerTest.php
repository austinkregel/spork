<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkDeploymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    public function test_deployment_detach_route_is_accessible()
    {
        $this->actingAsUser();

        $deployment = Deployment::factory()->create([
            'name' => 'Test Deployment',
            'project_id' => Project::factory()->create([
                'user_id' => $this->user->id,
            ])->id,
        ]);

        $credential = \App\Models\Credential::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $response = $this->post('http://spork.localhost/-/deployment/'.$deployment->id.'/detach', [
            'resource_type' => $credential::class,
            'resource_id' => $credential->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_deployment_attach_route_is_accessible()
    {
        $this->actingAsUser();

        $deployment = Deployment::factory()->create([
            'name' => 'Test Deployment',
            'project_id' => Project::factory()->create([
                'user_id' => $this->user->id,
            ])->id,
        ]);

        $credential = \App\Models\Credential::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseEmpty('deployment_resources');

        $response = $this->post('http://spork.localhost/-/deployment/'.$deployment->id.'/attach', [
            'resource_type' => $credential::class,
            'resource_id' => $credential->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('deployment_resources', 1);
    }

    public function test_project_deploy_route_is_accessible()
    {
        $this->actingAsUser();

        $deployment = Deployment::factory()->create([
            'name' => 'Test Deployment',
            'project_id' => Project::factory()->create([
                'user_id' => $this->user->id,
            ])->id,
        ]);

        $credentialCloudflare = \App\Models\Credential::factory()->create([
            'user_id' => $this->user->id,
            'service' => Credential::CLOUDFLARE,
            'type' => Credential::TYPE_DOMAIN,
        ]);
        $credentialNamecheap = \App\Models\Credential::factory()->create([
            'user_id' => $this->user->id,
            'type' => Credential::TYPE_REGISTRAR,
            'service' => Credential::NAMECHEAP,
        ]);
        \DB::table('deployment_resources')->insert([
            'resource_type' => $credentialCloudflare::class,
            'resource_id' => $credentialCloudflare->id,
            'deployment_id' => $deployment->id,
            'settings' => json_encode([]),
        ]);
        \DB::table('deployment_resources')->insert([
            'resource_type' => $credentialNamecheap::class,
            'resource_id' => $credentialNamecheap->id,
            'deployment_id' => $deployment->id,
            'settings' => json_encode([]),
        ]);
        $deployment->load('credentials');
        $this->assertCount(2, $deployment->credentials);
        $response = $this->post('http://spork.localhost/-/deployment/'.$deployment->id.'/deploy');

        $response->assertStatus(200);
    }
}
