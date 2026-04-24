<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use App\Contracts\Services\ServerServiceContract;
use App\Models\Credential;
use App\Models\InfrastructureProvisionRequest;
use App\Models\User;
use App\Services\Factories\ServerServiceFactory;
use App\Services\Infrastructure\Provisioning\ServerProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ServerProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_persists_server_from_factory_service(): void
    {
        $user = User::factory()->create();

        $credential = Credential::factory()
            ->for($user, 'user')
            ->create([
                'type' => Credential::TYPE_SERVER,
                'service' => Credential::DIGITAL_OCEAN,
            ]);

        $request = InfrastructureProvisionRequest::query()->create([
            'user_id' => $user->id,
            'provider_credential_id' => $credential->id,
            'status' => 'pending',
            'payload' => [
                'server' => [
                    'name' => null,
                    'region' => 'nyc1',
                    'size' => 's-1vcpu-1gb',
                    'image' => 'ubuntu',
                    'ssh_key_ids' => [],
                ],
            ],
        ]);

        $fakeServerService = new class implements ServerServiceContract
        {
            public array $created = [];

            public function createServer(array $config): array
            {
                $this->created = $config;

                return ['id' => 456];
            }

            public function waitForActiveServer(int $identifier, int $attempts = 30, int $sleepSeconds = 1): array
            {
                return [
                    'id' => $identifier,
                    'name' => 'provisioned-'.$identifier,
                    'status' => 'active',
                    'cpu' => 2,
                    'memory' => 2048,
                    'disk' => 50,
                    'cost' => 0.01,
                    'networks' => [
                        'public_v4' => '198.51.100.10',
                        'private_v4' => '10.20.30.40',
                    ],
                ];
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
                return [];
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

        $factory = Mockery::mock(ServerServiceFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(Mockery::on(fn ($value) => $value->is($credential)))
            ->andReturn($fakeServerService);

        $service = new ServerProvisioningService($factory);

        $result = $service->handle($request->fresh());

        $this->assertDatabaseHas('servers', [
            'credential_id' => $credential->id,
            'status' => 'active',
            'ip_address' => '198.51.100.10',
        ]);

        $this->assertSame('198.51.100.10', $result['ip_address']);
        $this->assertNotEmpty($result['details']);
        $this->assertNotNull($result['model']?->id);
    }
}
