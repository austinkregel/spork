<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkDeploymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_detach_route_is_accessible()
    {
        $response = $this->post('/-/deployment/{deployment}/detach');

        $response->assertStatus(200);
    }

    public function test_deployment_attach_route_is_accessible()
    {
        $response = $this->post('/-/deployment/{deployment}/attach');

        $response->assertStatus(200);
    }

    public function test_project_deploy_route_is_accessible()
    {
        $response = $this->post('/-/deployment/{project}/deploy');

        $response->assertStatus(200);
    }

    public function test_deployment_detach_route_loads_expected_data()
    {
        $response = $this->post('/-/deployment/{deployment}/detach');

        $response->assertInertia(fn ($page) => $page
            ->component('DeploymentDetach')
            ->has('deployment')
        );
    }

    public function test_deployment_attach_route_loads_expected_data()
    {
        $response = $this->post('/-/deployment/{deployment}/attach');

        $response->assertInertia(fn ($page) => $page
            ->component('DeploymentAttach')
            ->has('deployment')
        );
    }

    public function test_project_deploy_route_loads_expected_data()
    {
        $response = $this->post('/-/deployment/{project}/deploy');

        $response->assertInertia(fn ($page) => $page
            ->component('ProjectDeploy')
            ->has('project')
        );
    }
}
