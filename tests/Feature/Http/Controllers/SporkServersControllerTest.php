<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkServersControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

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

    public function test_servers_route_loads_provider_optional_servers()
    {
        $this->actingAsUser();
        $user = $this->user;

        $ssh = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_SSH,
            'service' => Credential::TYPE_SSH,
        ]);

        $ssh->servers()->create([
            'server_id' => (string) Str::uuid(),
            'name' => 'baremetal-01',
            'status' => 'online',
            'connection_type' => 'agent',
            'machine_id' => 'machine-test-001',
        ]);

        $response = $this->actingAs($user)->get('http://spork.localhost/-/servers');

        $response->assertInertia(fn ($page) => $page
            ->component('Infrastructure/Index')
            ->has('servers')
            ->where('servers', fn ($servers) => collect($servers)
                ->contains(fn ($server) => ($server['name'] ?? null) === 'baremetal-01' && ($server['provider'] ?? null) === Credential::TYPE_SSH)
            )
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
