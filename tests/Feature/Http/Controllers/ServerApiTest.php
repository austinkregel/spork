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

    public function testServerThrowsValidationError()
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'api_key' => 'credential_api_key',
        ]);

        $response = $this->actingAs($user)->post('http://pending.download/api/servers', [], [
            'Accept' => 'application/json',
            'Authentication' => 'Bearer '.$credential->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'root@system:installer',
        ]);

        $response->assertStatus(422);
    }
    public function testServerCreateSuccessful()
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'api_key' => 'credential_api_key',
        ]);

        $response = $this->actingAs($user)
        ->postJson('http://pending.download/api/servers', [
            'server_id' => 'falef',
            'name' => 'falef',
            'ip_address' => '127.0.0.1',
            'port' => 22,
            'status' => 'provisioning'
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
