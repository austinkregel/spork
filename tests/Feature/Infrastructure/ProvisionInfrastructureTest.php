<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Contracts\Services\DigitalOceanServiceContract;
use App\Jobs\Infrastructure\ProvisionInfrastructureJob;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\InfrastructureProvisionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeDigitalOceanService;
use Tests\TestCase;

class ProvisionInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_provision_request_payload(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $computeCredential = Credential::factory()
            ->for($user, 'user')
            ->create([
                'type' => Credential::TYPE_SERVER,
                'service' => Credential::DIGITAL_OCEAN,
            ]);

        $dnsCredential = Credential::factory()
            ->for($user, 'user')
            ->create([
                'type' => Credential::TYPE_DOMAIN,
                'service' => Credential::CLOUDFLARE,
            ]);

        $sshCredential = Credential::factory()
            ->for($user, 'user')
            ->create([
                'type' => Credential::TYPE_SSH,
                'service' => Credential::TYPE_SSH,
                'settings' => [
                    'pub_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIEfakePublicKeyValueForTestOnly test@example.com',
                ],
            ]);

        $domain = Domain::factory()
            ->for($dnsCredential, 'credential')
            ->create([
                'name' => 'example.com',
                'expires_at' => now()->addYear(),
            ]);

        app()->bind(DigitalOceanServiceContract::class, fn ($app, $params) => new FakeDigitalOceanService);

        $this->actingAs($user, 'sanctum');

        $payload = [
            'provider_credential_id' => $computeCredential->id,
            'server' => [
                'name' => 'alpha-alpha-705',
                'region' => 'nyc1',
                'size' => 's-1vcpu-512mb-10gb',
                'image' => 'ubuntu-22-04-x64',
                'ssh_key_ids' => [$sshCredential->id],
                'tags' => [],
                'user_data' => '',
            ],
            'domain_action' => 'link',
            'existing_domain_id' => $domain->id,
            'dns_provider_credential_id' => $dnsCredential->id,
            'new_domain' => null,
            'records' => [
                [
                    'type' => 'A',
                    'name' => '@',
                    'value' => '',
                    'proxied' => true,
                    'use_server_ip' => true,
                ],
            ],
        ];

        $response = $this->postJson(route('api.infrastructure.provision.store'), $payload);

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.provider.id', $computeCredential->id)
            ->assertJsonPath('data.dns_provider.id', $dnsCredential->id);

        $this->assertDatabaseHas('infrastructure_provision_requests', [
            'provider_credential_id' => $computeCredential->id,
            'dns_credential_id' => $dnsCredential->id,
            'status' => 'pending',
        ]);

        $provisionRequest = InfrastructureProvisionRequest::query()->firstOrFail();
        $mappedKeyId = $provisionRequest->payload['server']['ssh_key_ids'][0] ?? null;
        $this->assertSame('provider-key-123', $mappedKeyId);

        $provisionRequestId = $provisionRequest->id;

        Queue::assertPushed(ProvisionInfrastructureJob::class, function (ProvisionInfrastructureJob $job) use ($provisionRequestId) {
            return $job->provisionRequestId === $provisionRequestId;
        });
    }
}
