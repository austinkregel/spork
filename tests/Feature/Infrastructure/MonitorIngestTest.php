<?php

namespace Tests\Feature\Infrastructure;

use App\Models\Credential;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonitorIngestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    public function test_it_creates_or_updates_server_by_hostname_on_ingest(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_DEVELOPMENT,
            'service' => 'monitor-bridge',
            'api_key' => 'bridge-token-'.Str::random(12),
        ]);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/monitor/ingest', [
            'event_type' => 'stats',
            'payload' => [
                'clientId' => 'baremetal-01',
                'data' => [
                    'load' => 0.42,
                ],
            ],
        ], [
            'Authentication' => 'Bearer '.$credential->api_key,
        ]);

        $response->assertStatus(202);
        $this->assertDatabaseHas('servers', [
            'name' => 'baremetal-01',
            'credential_id' => $credential->id,
        ]);

        /** @var Server $server */
        $server = Server::query()->where('name', 'baremetal-01')->firstOrFail();
        $this->assertNotNull($server->last_ping_at);
        $this->assertIsArray($server->telemetry);
        $this->assertSame('stats', $server->telemetry['event_type'] ?? null);
    }

    public function test_it_rejects_missing_authentication(): void
    {
        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/monitor/ingest', [
            'event_type' => 'stats',
            'payload' => [
                'clientId' => 'baremetal-01',
            ],
        ]);

        $response->assertForbidden();
    }
}


