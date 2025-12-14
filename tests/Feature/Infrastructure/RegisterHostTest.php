<?php

namespace Tests\Feature\Infrastructure;

use App\Models\Credential;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegisterHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    public function test_it_registers_a_host_with_machine_id(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_SSH,
            'service' => Credential::TYPE_SSH,
            'api_key' => 'test-token-'.Str::random(12),
        ]);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/hosts/register', [
            'machine_id' => 'machine-'.Str::random(12),
            'name' => 'baremetal-01',
            'ip_address' => '203.0.113.10',
        ], [
            'Authentication' => 'Bearer '.$credential->api_key,
        ]);

        $response->assertOk();
        $response->assertJsonPath('created', true);
        $response->assertJsonPath('server.name', 'baremetal-01');

        $this->assertDatabaseHas('servers', [
            'credential_id' => $credential->id,
            'name' => 'baremetal-01',
            'ip_address' => '203.0.113.10',
        ]);
    }

    public function test_it_rejects_missing_or_invalid_authentication_header(): void
    {
        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/hosts/register', [
            'machine_id' => 'machine-'.Str::random(12),
            'name' => 'baremetal-01',
        ]);

        $response->assertForbidden();
    }

    public function test_it_is_idempotent_for_the_same_machine_id_under_same_credential(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_SSH,
            'service' => Credential::TYPE_SSH,
            'api_key' => 'test-token-'.Str::random(12),
        ]);

        $machineId = 'machine-'.Str::random(12);

        $first = $this->postJson('http://echo.kregel.dev/api/infrastructure/hosts/register', [
            'machine_id' => $machineId,
            'name' => 'baremetal-01',
        ], [
            'Authentication' => 'Bearer '.$credential->api_key,
        ]);

        $first->assertOk();
        $first->assertJsonPath('created', true);

        $second = $this->postJson('http://echo.kregel.dev/api/infrastructure/hosts/register', [
            'machine_id' => $machineId,
            'name' => 'baremetal-01-renamed',
        ], [
            'Authentication' => 'Bearer '.$credential->api_key,
        ]);

        $second->assertOk();
        $second->assertJsonPath('created', false);
        $second->assertJsonPath('server.name', 'baremetal-01-renamed');

        $this->assertSame(1, Server::query()->where('machine_id', $machineId)->count());
    }

    public function test_it_refuses_to_register_a_machine_id_owned_by_another_credential(): void
    {
        $credentialA = Credential::factory()->create([
            'type' => Credential::TYPE_SSH,
            'service' => Credential::TYPE_SSH,
            'api_key' => 'test-token-a-'.Str::random(12),
        ]);

        $credentialB = Credential::factory()->create([
            'type' => Credential::TYPE_SSH,
            'service' => Credential::TYPE_SSH,
            'api_key' => 'test-token-b-'.Str::random(12),
        ]);

        $machineId = 'machine-'.Str::random(12);

        $server = $credentialA->servers()->create([
            'server_id' => (string) Str::uuid(),
            'machine_id' => $machineId,
            'connection_type' => 'agent',
            'name' => 'baremetal-01',
            'status' => 'enrolling',
        ]);

        $this->assertNotNull($server->id);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/hosts/register', [
            'machine_id' => $machineId,
            'name' => 'attempted-hijack',
        ], [
            'Authentication' => 'Bearer '.$credentialB->api_key,
        ]);

        $response->assertStatus(409);
    }
}


