<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\Services\DigitalOceanServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class FakeDigitalOceanService implements DigitalOceanServiceContract
{
    public function __construct(...$args)
    {
    }

    public function createServer(array $config): array
    {
        return [
            'id' => 123,
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

    public function removeServerKey($identifier): void
    {
    }

    public function deleteServer(int|string $identifier): void
    {
    }

    public function powerOnServer(int|string $identifier): void
    {
    }

    public function powerOffServer(int|string $identifier): void
    {
    }

    public function shutdownServer(int|string $identifier): void
    {
    }

    public function rebootServer(int|string $identifier): void
    {
    }

    public function waitForActiveServer(int $identifier, int $attempts = 30, int $sleepSeconds = 1): array
    {
        return [
            'id' => $identifier,
            'name' => 'fake-droplet',
            'status' => 'active',
            'cpu' => 1,
            'memory' => 512,
            'disk' => 10,
            'cost' => 0.00744,
            'networks' => [
                'public_v4' => '192.0.2.1',
                'private_v4' => '10.0.0.5',
            ],
        ];
    }

    public function findAllSshkeys(): array
    {
        return [];
    }

    public function createSshKey(string $name, string $publicKey): array
    {
        return [
            'id' => 'provider-key-123',
            'fingerprint' => 'fingerprint-generated',
        ];
    }

    public function findSshKeyByFingerprint(?string $fingerprint): ?array
    {
        return null;
    }

    public function createDomain(string $domain): array
    {
        return [];
    }

    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return new Paginator([], 0, $limit, $page);
    }

    public function getDns(string $domain, string $type = 'A', int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return new Paginator([], 0, $limit, $page);
    }

    public function deleteDnsRecord(string $domain, string $dnsRecordId): void
    {
    }

    public function createDnsRecord(string $domain, array $dnsRecordArray): void
    {
    }
}

