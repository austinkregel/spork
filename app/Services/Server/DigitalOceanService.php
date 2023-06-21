<?php

namespace App\Services\Server;

use App\Contracts\Services\DigitalOceanServiceContract;
use App\Models\Credential;
use App\Services\Filters\DigitalOceanServerFilter;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DigitalOceanServer;
use DigitalOceanV2\Entity\Key;
use DigitalOceanV2\Entity\Region;
use DigitalOceanV2\Entity\Region as DigitalOceanRegion;
use DigitalOceanV2\Entity\Size as DigitalOceanSize;
use GrahamCampbell\DigitalOcean\DigitalOceanFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DigitalOceanService implements DigitalOceanServiceContract
{
    /**
     * @var Client
     */
    protected $digitalOcean;

    protected DigitalOceanServerFilter $serverFilter;

    public function __construct(Credential $credential)
    {
        $this->digitalOcean = app(DigitalOceanFactory::class)->make([
            'token' => $credential->access_token,
            'method' => 'token',
        ]);

        $this->serverFilter = new DigitalOceanServerFilter();
    }
    public function createServer(array $config): array
    {
        return [];
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

    public function removeServerKey($identifier): void
    {
//        $this->digitalOcean->key()->delete($identifier);
    }

    public function deleteServer(int $identifier): void
    {
//        $this->digitalOcean->droplet()->delete($identifier);
    }

    public function powerOnServer(int $identifier): void
    {
        $this->digitalOcean->droplet()->powerOn($identifier);
    }

    public function powerOffServer(int $identifier): void
    {
        $this->digitalOcean->droplet()->powerOff($identifier);
    }

    public function shutdownServer(int $identifier): void
    {
        $this->digitalOcean->droplet()->shutdown($identifier);
    }

    public function rebootServer(int $identifier): void
    {
        $this->digitalOcean->droplet()->reboot($identifier);
    }

    public function findAllSshkeys(): array
    {
        $keys = $this->digitalOcean->key()->getAll();

        return array_map(function (Key $key): SshKey {
            return new SshKey($key->toArray());
        }, $keys);
    }

    public function getDomains(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        // TODO: Implement getDomains() method.
    }

    public function getDns(string $domain, string $type = 'A', int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        // TODO: Implement getDns() method.
    }

    public function deleteDnsRecord(string $domain, string $dnsRecordId): void
    {
        // TODO: Implement deleteDnsRecord() method.
    }

    public function createDnsRecord(string $domain, array $dnsRecordArray): void
    {
        // TODO: Implement createDnsRecord() method.
    }

    public function getDomainNs(string $domain): array
    {
        // TODO: Implement getDomainNs() method.
    }

    public function updateDomainNs(string $domain, array $nameservers): array
    {
        // TODO: Implement updateDomainNs() method.
    }
}
