<?php

declare(strict_types=1);

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
            'type' => Credential::TYPE_BACKUP_AGENT,
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

    public function test_it_accepts_but_does_not_create_server_when_missing_client_id(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_BACKUP_AGENT,
            'service' => 'monitor-bridge',
            'api_key' => 'bridge-token-'.Str::random(12),
        ]);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/monitor/ingest', [
            'event_type' => 'stats',
            'payload' => [
                'data' => [
                    'load' => 0.42,
                ],
            ],
        ], [
            'Authentication' => 'Bearer '.$credential->api_key,
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'accepted' => false,
            'reason' => 'missing_client_id',
        ]);

        $this->assertSame(0, Server::query()->count());
    }

    public function test_it_matches_existing_server_by_machine_id_and_does_not_create_duplicate(): void
    {
        $providerCredential = Credential::factory()->create([
            'type' => Credential::TYPE_SERVER,
            'service' => Credential::DIGITAL_OCEAN,
        ]);

        /** @var Server $providerServer */
        $providerServer = Server::factory()->create([
            'credential_id' => $providerCredential->id,
            'provider_credential_id' => $providerCredential->id,
            'provider_server_id' => 'do-123',
            'connection_type' => 'provider',
            'name' => 'do-web-01',
            'status' => 'active',
            'machine_id' => 'machine-abc',
            'ip_address' => '203.0.113.10',
        ]);

        $monitorCredential = Credential::factory()->create([
            'type' => Credential::TYPE_BACKUP_AGENT,
            'service' => 'monitor-bridge',
            'api_key' => 'bridge-token-'.Str::random(12),
        ]);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/monitor/ingest', [
            'event_type' => 'agent_stats',
            'payload' => [
                'clientId' => 'baremetal-01',
                'data' => [
                    'machine_id' => 'machine-abc',
                    'status' => 'online',
                    'ip' => '203.0.113.10',
                ],
            ],
        ], [
            'Authentication' => 'Bearer '.$monitorCredential->api_key,
        ]);

        $response->assertStatus(202)->assertJson(['accepted' => true, 'server_id' => $providerServer->id]);
        $this->assertSame(1, Server::query()->count());

        $providerServer->refresh();
        $this->assertNotNull($providerServer->last_ping_at);
        $this->assertIsArray($providerServer->telemetry);
        $this->assertSame('agent_stats', $providerServer->telemetry['event_type'] ?? null);

        // Provider precedence: do not overwrite provider-owned name/status with agent-reported values.
        $this->assertSame('do-web-01', $providerServer->name);
        $this->assertSame('active', $providerServer->status);
    }

    public function test_it_hydrates_ip_fields_from_stats_net_ifaces(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_BACKUP_AGENT,
            'service' => 'monitor-bridge',
            'api_key' => 'bridge-token-'.Str::random(12),
        ]);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/monitor/ingest', [
            'event_type' => 'stats',
            'payload' => [
                'clientId' => 'bubbling-brook',
                'data' => [
                    'netIfaces' => [
                        [
                            'name' => 'eth0',
                            'family' => 'IPv4',
                            'address' => '162.243.54.67',
                            'cidr' => '162.243.54.0/24',
                            'internal' => false,
                        ],
                        [
                            'name' => 'eth0',
                            'family' => 'IPv4',
                            'address' => '10.13.0.5',
                            'cidr' => '10.13.0.0/16',
                            'internal' => true,
                        ],
                        [
                            'name' => 'eth0',
                            'family' => 'IPv6',
                            'address' => '2001:db8::1',
                            'cidr' => '2001:db8::/64',
                            'internal' => false,
                        ],
                        [
                            'name' => 'eth0',
                            'family' => 'IPv6',
                            'address' => 'fd00::1',
                            'cidr' => 'fd00::/64',
                            'internal' => true,
                        ],
                    ],
                ],
            ],
        ], [
            'Authentication' => 'Bearer '.$credential->api_key,
        ]);

        $response->assertStatus(202)->assertJson(['accepted' => true]);

        /** @var Server $server */
        $server = Server::query()->where('name', 'bubbling-brook')->firstOrFail();
        $this->assertSame('162.243.54.67', $server->ip_address);
        $this->assertSame('10.13.0.5', $server->internal_ip_address);
        $this->assertSame('2001:db8::1', $server->ip_address_v6);
        $this->assertSame('fd00::1', $server->internal_ip_address_v6);
    }

    public function test_it_does_not_clobber_existing_ip_fields_when_hydrating_from_net_ifaces(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_BACKUP_AGENT,
            'service' => 'monitor-bridge',
            'api_key' => 'bridge-token-'.Str::random(12),
        ]);

        /** @var Server $server */
        $server = Server::factory()->create([
            'credential_id' => $credential->id,
            'connection_type' => 'agent',
            'name' => 'bubbling-brook',
            'ip_address' => '203.0.113.10',
            'internal_ip_address' => null,
        ]);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/monitor/ingest', [
            'event_type' => 'stats',
            'payload' => [
                'clientId' => 'bubbling-brook',
                'data' => [
                    'netIfaces' => [
                        [
                            'name' => 'eth0',
                            'family' => 'IPv4',
                            'address' => '162.243.54.67',
                            'internal' => false,
                        ],
                        [
                            'name' => 'eth0',
                            'family' => 'IPv4',
                            'address' => '10.13.0.5',
                            'internal' => true,
                        ],
                    ],
                ],
            ],
        ], [
            'Authentication' => 'Bearer '.$credential->api_key,
        ]);

        $response->assertStatus(202)->assertJson(['accepted' => true, 'server_id' => $server->id]);

        $server->refresh();
        $this->assertSame('203.0.113.10', $server->ip_address);
        $this->assertSame('10.13.0.5', $server->internal_ip_address);
    }

    public function test_it_sets_machine_id_on_existing_server_when_matched_by_ip(): void
    {
        $providerCredential = Credential::factory()->create([
            'type' => Credential::TYPE_SERVER,
            'service' => Credential::DIGITAL_OCEAN,
        ]);

        /** @var Server $providerServer */
        $providerServer = Server::factory()->create([
            'credential_id' => $providerCredential->id,
            'provider_credential_id' => $providerCredential->id,
            'provider_server_id' => 'do-456',
            'connection_type' => 'provider',
            'name' => 'do-web-02',
            'status' => 'active',
            'machine_id' => null,
            'ip_address' => '203.0.113.11',
        ]);

        $monitorCredential = Credential::factory()->create([
            'type' => Credential::TYPE_BACKUP_AGENT,
            'service' => 'monitor-bridge',
            'api_key' => 'bridge-token-'.Str::random(12),
        ]);

        $response = $this->postJson('http://echo.kregel.dev/api/infrastructure/monitor/ingest', [
            'event_type' => 'net_status',
            'payload' => [
                'clientId' => 'agent-02',
                'data' => [
                    'ip' => '203.0.113.11',
                    'machine_id' => 'machine-def',
                ],
            ],
        ], [
            'Authentication' => 'Bearer '.$monitorCredential->api_key,
        ]);

        $response->assertStatus(202)->assertJson(['accepted' => true, 'server_id' => $providerServer->id]);

        $providerServer->refresh();
        $this->assertSame('machine-def', $providerServer->machine_id);
    }
}
