<?php

declare(strict_types=1);

namespace App\Services\Infrastructure\Provisioning;

use App\Models\InfrastructureProvisionRequest;
use App\Models\Server;
use App\Services\Factories\ServerServiceFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class ServerProvisioningService
{
    public function __construct(
        private readonly ServerServiceFactory $serverServiceFactory,
    ) {}

    public function handle(InfrastructureProvisionRequest $request): array
    {
        $credential = $request->providerCredential;

        if (! $credential) {
            throw new RuntimeException('Provision request is missing a compute credential.');
        }

        $serverPayload = $this->resolveServerPayload($request);
        $service = $this->serverServiceFactory->make($credential);

        $createResponse = $service->createServer($serverPayload);
        $providerServerId = $this->extractProviderServerId($createResponse);
        $serverDetails = $this->resolveServerDetails($service, $providerServerId, $createResponse);

        $serverModel = $this->storeServer(
            credentialId: $credential->id,
            serverPayload: $serverPayload,
            serverDetails: $serverDetails,
            providerServerId: $providerServerId,
        );

        return [
            'id' => $serverModel->id,
            'model' => $serverModel,
            'ip_address' => $this->resolveNetworkValue($serverDetails, 'public_v4'),
            'details' => $serverDetails,
        ];
    }

    protected function resolveServerPayload(InfrastructureProvisionRequest $request): array
    {
        $payload = $request->payload['server'] ?? null;

        if (! is_array($payload)) {
            throw new RuntimeException('Provision request must include server configuration data.');
        }

        $payload['name'] = ($payload['name'] ?? null) ?: sprintf('infra-%s', Str::random(6));

        return $payload;
    }

    protected function extractProviderServerId(array $response): ?string
    {
        $candidates = [
            Arr::get($response, 'id'),
            Arr::get($response, 'server.id'),
            Arr::get($response, 'droplet.id'),
            Arr::get($response, 'data.id'),
        ];

        foreach ($candidates as $candidate) {
            if (! empty($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    protected function resolveServerDetails(object $service, ?string $providerServerId, array $fallback): array
    {
        if ($providerServerId !== null && method_exists($service, 'waitForActiveServer')) {
            $details = $service->waitForActiveServer((int) $providerServerId);

            if (! empty($details)) {
                return $details;
            }
        }

        return $fallback;
    }

    protected function storeServer(
        int $credentialId,
        array $serverPayload,
        array $serverDetails,
        ?string $providerServerId,
    ): Server {
        return Server::query()->create([
            'credential_id' => $credentialId,
            'server_id' => $providerServerId ?? (string) Str::uuid(),
            'name' => $serverDetails['name'] ?? $serverPayload['name'],
            'status' => $serverDetails['status'] ?? 'provisioning',
            'vcpu' => $serverDetails['vcpu'] ?? $serverDetails['cpu'] ?? null,
            'memory' => $serverDetails['memory'] ?? null,
            'disk' => $serverDetails['disk'] ?? null,
            'cost_per_hour' => $serverDetails['cost_per_hour'] ?? $serverDetails['cost'] ?? null,
            'ip_address' => $this->resolveNetworkValue($serverDetails, 'public_v4'),
            'ip_address_v6' => $this->resolveNetworkValue($serverDetails, 'public_v6'),
            'internal_ip_address' => $this->resolveNetworkValue($serverDetails, 'private_v4'),
            'internal_ip_address_v6' => $this->resolveNetworkValue($serverDetails, 'private_v6'),
            'os' => $serverPayload['image'] ?? Arr::get($serverDetails, 'image'),
        ]);
    }

    protected function resolveNetworkValue(array $serverDetails, string $key): ?string
    {
        $networks = Arr::get($serverDetails, 'networks');

        if (is_array($networks) && array_key_exists($key, $networks)) {
            $value = $networks[$key];

            if (is_array($value)) {
                return $value['ip_address'] ?? $value['address'] ?? null;
            }

            return $value;
        }

        return Arr::get($serverDetails, $key);
    }
}
