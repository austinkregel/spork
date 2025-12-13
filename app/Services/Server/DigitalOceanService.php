<?php

declare(strict_types=1);

namespace App\Services\Server;

use App\Contracts\Services\DigitalOceanServiceContract;
use App\Models\Credential;
use App\Services\Filters\DigitalOceanServerFilter;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Domain;
use DigitalOceanV2\Entity\DomainRecord;
use DigitalOceanV2\Entity\Droplet as DigitalOceanServer;
use DigitalOceanV2\Entity\Droplet as DigitalOceanDroplet;
use DigitalOceanV2\Entity\Key;
use DigitalOceanV2\Entity\Region;
use DigitalOceanV2\Entity\Region as DigitalOceanRegion;
use DigitalOceanV2\Entity\Size as DigitalOceanSize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Throwable;

class DigitalOceanService implements DigitalOceanServiceContract
{
    protected Client $digitalOcean;

    protected DigitalOceanServerFilter $serverFilter;

    public function __construct(Credential $credential)
    {
        $this->digitalOcean = app(Client::class);

        $this->digitalOcean->authenticate($credential->api_key);

        $this->serverFilter = new DigitalOceanServerFilter;
    }

    public function createServer(array $config): array
    {
        $droplet = $this->digitalOcean->droplet()->create(
            $config['name'],
            $config['region'],
            $config['size'],
            $config['image'] ?? 'ubuntu-22-04-x64',
            (bool) ($config['backups'] ?? false),
            (bool) ($config['ipv6'] ?? false),
            $config['private_networking'] ?? false,
            $config['ssh_keys'] ?? [],
            $config['user_data'] ?? '',
            (bool) ($config['monitoring'] ?? true),
            $config['volumes'] ?? [],
            $config['tags'] ?? [],
            (bool) ($config['disable_agent'] ?? false)
        );

        return $droplet->toArray();
    }

    public function createSshKey(string $name, string $publicKey): array
    {
        $key = $this->digitalOcean->key()->create($name, $publicKey);

        return $key->toArray();
    }

    public function findSshKeyByFingerprint(?string $fingerprint): ?array
    {
        if (empty($fingerprint)) {
            return null;
        }

        try {
            $key = $this->digitalOcean->key()->getByFingerprint($fingerprint);

            return $key->toArray();
        } catch (Throwable $exception) {
            return null;
        }
    }

    public function findAllRegions(): array
    {
        $regions = $this->digitalOcean->region()->getAll();

        return array_map(function (DigitalOceanRegion $region) {
            return new Region($region->toArray());
        }, $regions);
    }

    public function findAllSizes(): array
    {
        $sizes = $this->digitalOcean->size()->getAll();

        return array_map(function (DigitalOceanSize $size) {
            return $size->toArray();
        }, $sizes);
    }

    public function findAllServers(): array
    {
        $servers = $this->digitalOcean->droplet()->getAll();

        return array_map(function (DigitalOceanServer $server) {
            return $this->serverFilter->filter($server);
        }, $servers);
    }

    public function findServer(int $identifier): array
    {
        $server = $this->digitalOcean->droplet()->getById($identifier);

        return $this->serverFilter->filter($server);
    }

    public function waitForActiveServer(int $identifier, int $attempts = 30, int $sleepSeconds = 10): array
    {
        $latest = [];

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            /** @var DigitalOceanDroplet $droplet */
            $droplet = $this->digitalOcean->droplet()->getById($identifier);
            $latest = $this->serverFilter->filter($droplet);

            $isActive = ($latest['status'] ?? null) === 'active';
            $hasIp = ! empty($latest['networks']['public_v4'] ?? null);

            if ($isActive && $hasIp) {
                break;
            }

            sleep($sleepSeconds);
        }

        return $latest;
    }

    public function removeServerKey($identifier): void
    {
        //        $this->digitalOcean->key()->delete($identifier);
    }

    public function deleteServer(int|string $identifier): void
    {
        //        $this->digitalOcean->droplet()->delete($identifier);
    }

    public function powerOnServer(int|string $identifier): void
    {
        $this->digitalOcean->droplet()->powerOn($identifier);
    }

    public function powerOffServer(int|string $identifier): void
    {
        $this->digitalOcean->droplet()->powerOff($identifier);
    }

    public function shutdownServer(int|string $identifier): void
    {
        $this->digitalOcean->droplet()->shutdown($identifier);
    }

    public function rebootServer(int|string $identifier): void
    {
        $this->digitalOcean->droplet()->reboot($identifier);
    }

    public function findAllSshkeys(): array
    {
        $keys = $this->digitalOcean->key()->getAll();

        return array_map(function (Key $key): Key {
            return $key->toArray();
        }, $keys);
    }

    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $items = $this->digitalOcean->domain()->getAll();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            array_map(function (Domain $domain) {
                return $domain->toArray();
            }, $items),
            count($items),
            $limit,
            $page,
        );
    }

    public function getDns(string $domain, string $type = '', int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        $items = $this->digitalOcean->domainRecord()->getAll($domain);

        $domains = array_map(function (DomainRecord $domain) {
            return $domain->toArray();
        }, $items);

        if (! empty($type)) {
            $domains = array_filter($domains, function ($domain) use ($type) {
                return $domain['type'] === $type;
            });
        }

        return new \Illuminate\Pagination\LengthAwarePaginator(
            array_values($domains),
            count($domains),
            $limit,
            $page,
        );
    }

    public function deleteDnsRecord(string $domain, string $dnsRecordId): void
    {
        $this->digitalOcean->domainRecord()->delete($domain, (int) $dnsRecordId);
    }

    public function createDnsRecord(string $domain, array $dnsRecordArray): void
    {
        $data = $dnsRecordArray['data'] ?? $dnsRecordArray['content'] ?? null;

        if ($data === null) {
            return;
        }

        $this->digitalOcean->domainRecord()->create(
            $domain,
            $dnsRecordArray['type'],
            $dnsRecordArray['name'],
            $data,
            $dnsRecordArray['priority'] ?? null,
        );
    }

    public function getDomainNs(string $domain): array
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $this->getDns($domain, 'NS', 1000, 1);

        return array_values(array_map(
            fn (array $record) => $record['data'] ?? $record['name'] ?? '',
            $paginator->items()
        ));
    }

    public function updateDomainNs(string $domain, array $nameservers): array
    {
        $existing = $this->getDns($domain, 'NS', 1000, 1)->items();

        foreach ($existing as $record) {
            if (($record['type'] ?? null) === 'NS' && isset($record['id'])) {
                $this->deleteDnsRecord($domain, (string) $record['id']);
            }
        }

        foreach ($nameservers as $ns) {
            $this->createDnsRecord($domain, [
                'type' => 'NS',
                'name' => '@',
                'data' => $ns,
            ]);
        }

        return array_values($nameservers);
    }

    public function createDomain(string $domain): array
    {
        $this->digitalOcean->domain()->create($domain);

        // DigitalOcean uses a fixed set of nameservers for all domains.
        return [
            'ns1.digitalocean.com',
            'ns2.digitalocean.com',
            'ns3.digitalocean.com',
        ];
    }
}
