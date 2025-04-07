<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_throws_validation_error(): void
    {
        $user = $this->createUserWithPermissions([
            'create_server',
        ]);
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'api_key' => 'credential_api_key',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('server.create'), [], [
                'Accept' => 'application/json',
                'Authentication' => 'Bearer '.$credential->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'root@system:installer',
            ]);

        $response->assertStatus(422);
    }

    public function test_server_create_successful(): void
    {
        $user = $this->createUserWithPermissions([
            'create_server',
        ]);
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'api_key' => 'credential_api_key',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('server.create'), [
                'server_id' => 'falef',
                'name' => 'falef',
                'ip_address' => '127.0.0.1',
                'port' => 22,
                'status' => 'provisioning',
            ], [
                'Accept' => 'application/json',
                'Authentication' => 'Bearer '.$credential->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'root@system:installer',
            ]);

        $response->assertStatus(200);

        $body = $response->json();

        // The server's creation, returns the access token which can only edit itself.
        $this->assertNotEmpty($body['access_token']);
        $this->assertSame('falef', $body['name']);
    }
}
