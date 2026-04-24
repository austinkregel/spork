<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    public function test_server_throws_validation_error(): void
    {
        // Note: This endpoint uses credential-based authentication, not user permissions
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'api_key' => 'credential_api_key',
        ]);

        $response = $this->postJson(route('server.create'), [], [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$credential->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'root@system:installer',
        ]);

        $response->assertStatus(422);
        // Verify that validation errors are present (exact fields depend on database schema)
        $this->assertNotEmpty($response->json('errors'));
    }

    public function test_server_create_successful(): void
    {
        // Note: This endpoint uses credential-based authentication, not user permissions
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'api_key' => 'credential_api_key',
        ]);

        $response = $this->postJson(route('server.create'), [
            'server_id' => 'falef',
            'name' => 'falef',
            'ip_address' => '127.0.0.1',
            'status' => 'provisioning',
        ], [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$credential->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'root@system:installer',
        ]);

        $response->assertStatus(200);

        $body = $response->json();

        // The server's creation returns the access token which can only edit itself.
        $this->assertNotEmpty($body['access_token']);
        $this->assertSame('falef', $body['name']);

        // Verify the server was actually created in the database
        $this->assertDatabaseHas('servers', [
            'credential_id' => $credential->id,
            'server_id' => 'falef',
            'name' => 'falef',
            'ip_address' => '127.0.0.1',
            'status' => 'provisioning',
        ]);
    }
}
