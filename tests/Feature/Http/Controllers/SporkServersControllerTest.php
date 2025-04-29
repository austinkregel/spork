<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkServersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_servers_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/servers');

        $response->assertStatus(200);
    }

    public function test_servers_server_route_is_accessible()
    {
        $server = \App\Models\Server::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/servers/{$server->id}");

        $response->assertStatus(200);
    }

    public function test_servers_server_console_route_is_accessible()
    {
        $server = \App\Models\Server::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/servers/{$server->id}/console");

        $response->assertStatus(200);
    }

    public function test_servers_server_keys_route_is_accessible()
    {
        $server = \App\Models\Server::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/servers/{$server->id}/keys");

        $response->assertStatus(200);
    }

    public function test_servers_server_workers_route_is_accessible()
    {
        $server = \App\Models\Server::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/servers/{$server->id}/workers");

        $response->assertStatus(200);
    }

    public function test_servers_server_crontab_route_is_accessible()
    {
        $server = \App\Models\Server::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/servers/{$server->id}/crontab");

        $response->assertStatus(200);
    }

    public function test_servers_server_logs_route_is_accessible()
    {
        $server = \App\Models\Server::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/servers/{$server->id}/logs");

        $response->assertStatus(200);
    }

    public function test_servers_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/servers');

        $response->assertInertia(fn ($page) => $page
            ->component('Infrastructure/Index')
            ->has('servers')
        );
    }

    public function test_servers_server_route_loads_expected_data()
    {
        $server = \App\Models\Server::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/servers/{$server->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Infrastructure/Show')
            ->has('server')
        );
    }
}
