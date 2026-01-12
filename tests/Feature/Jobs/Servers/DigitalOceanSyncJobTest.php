<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs\Servers;

use App\Contracts\Services\ServerServiceContract;
use App\Jobs\Servers\DigitalOceanSyncJob;
use App\Models\Credential;
use App\Models\Server;
use App\Services\Factories\ServerServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalOceanSyncJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_idempotent_and_does_not_create_duplicate_servers(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_SERVER,
            'service' => Credential::DIGITAL_OCEAN,
        ]);

        $payload = [
            [
                'id' => 1001,
                'name' => 'do-1',
                'status' => 'active',
                'cpu' => 2,
                'memory' => 2048,
                'disk' => 50,
                'cost' => 0.01,
                'image' => 'ubuntu',
                'networks' => [
                    'public_v4' => '1.1.1.1',
                    'public_v6' => null,
                    'private_v4' => '10.0.0.1',
                    'private_v6' => null,
                ],
            ],
            [
                'id' => 1002,
                'name' => 'do-2',
                'status' => 'active',
                'cpu' => 4,
                'memory' => 4096,
                'disk' => 80,
                'cost' => 0.02,
                'image' => 'ubuntu',
                'networks' => [
                    'public_v4' => '2.2.2.2',
                    'public_v6' => null,
                    'private_v4' => '10.0.0.2',
                    'private_v6' => null,
                ],
            ],
        ];

        $job = new DigitalOceanSyncJob($credential, $credential->user);
        $job->handle($this->fakeFactoryReturningServers($payload));

        $this->assertSame(2, Server::query()->where('credential_id', $credential->id)->count());

        // Running again should not create duplicates.
        $job2 = new DigitalOceanSyncJob($credential, $credential->user);
        $job2->handle($this->fakeFactoryReturningServers($payload));

        $this->assertSame(2, Server::query()->where('credential_id', $credential->id)->count());
        $this->assertSame(1, Server::query()->where('credential_id', $credential->id)->where('server_id', '1001')->count());
        $this->assertSame(1, Server::query()->where('credential_id', $credential->id)->where('server_id', '1002')->count());
    }

    #[Test]
    public function it_prunes_servers_that_no_longer_exist_upstream(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_SERVER,
            'service' => Credential::DIGITAL_OCEAN,
        ]);

        Server::query()->create([
            'credential_id' => $credential->id,
            'provider_credential_id' => $credential->id,
            'provider_server_id' => '9999',
            'connection_type' => 'provider',
            'server_id' => '9999',
            'name' => 'old-do',
            'status' => 'active',
        ]);

        $payload = [
            [
                'id' => 1001,
                'name' => 'do-1',
                'status' => 'active',
                'cpu' => 2,
                'memory' => 2048,
                'disk' => 50,
                'cost' => 0.01,
                'image' => 'ubuntu',
                'networks' => [
                    'public_v4' => '1.1.1.1',
                    'public_v6' => null,
                    'private_v4' => '10.0.0.1',
                    'private_v6' => null,
                ],
            ],
        ];

        $job = new DigitalOceanSyncJob($credential, $credential->user);
        $job->handle($this->fakeFactoryReturningServers($payload));

        $this->assertSame(1, Server::query()->where('credential_id', $credential->id)->count());
        $this->assertNotNull(Server::query()->where('credential_id', $credential->id)->where('server_id', '1001')->first());
        $this->assertNull(Server::query()->where('credential_id', $credential->id)->where('server_id', '9999')->first());
    }

    private function fakeFactoryReturningServers(array $servers): ServerServiceFactory
    {
        $service = new class($servers) implements ServerServiceContract
        {
            public function __construct(private readonly array $servers) {}

            public function createServer(array $config): array
            {
                return [];
            }

            public function findAllRegions(): array
            {
                return [];
            }

            public function findAllSizes(): array
            {
                return [];
            }

            public function findAllServers(): array
            {
                return $this->servers;
            }

            public function removeServerKey($identifier): void {}

            public function deleteServer(int|string $identifier): void {}

            public function powerOnServer(int|string $identifier): void {}

            public function powerOffServer(int|string $identifier): void {}

            public function shutdownServer(int|string $identifier): void {}

            public function rebootServer(int|string $identifier): void {}

            public function findAllSshkeys(): array
            {
                return [];
            }

            public function createSshKey(string $name, string $publicKey): array
            {
                return [];
            }

            public function findSshKeyByFingerprint(?string $fingerprint): ?array
            {
                return null;
            }
        };

        return new class($service) extends ServerServiceFactory
        {
            public function __construct(private readonly ServerServiceContract $service) {}

            public function make(Credential $credential): ServerServiceContract
            {
                return $this->service;
            }
        };
    }
}
