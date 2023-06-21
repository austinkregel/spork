<?php

declare(strict_types=1);

namespace App\Services\Server;

use App\Contracts\Services\ServerServiceContract;
use Ovh\Api;

class OVHCloudService implements ServerServiceContract
{
    protected Api $ovh;

    public function __construct($applicationKey, $applicationSecret, $consumerKey, $endpoint)
    {
        $this->ovh = new Api($applicationKey, $applicationSecret, $endpoint, $consumerKey);
    }

    public function createServer(array $config): array
    {
        $result = $this->ovh->post("/cloud/project/{$config['projectId']}/instance", [
            'flavorId' => $config['flavorId'],
            'imageId' => $config['imageId'],
            'name' => $config['name'],
            'region' => $config['region'],
            'sshKeyId' => $config['sshKeyId'],
        ]);

        return $result;
    }

    public function findAllRegions(): array
    {
        $result = $this->ovh->get('/cloud/region');

        return $result;
    }

    public function findAllSizes(): array
    {
        $result = $this->ovh->get('/cloud/flavor');

        return $result;
    }

    public function findAllServers(): array
    {
        $result = $this->ovh->get('/cloud/project/instance');

        return $result;
    }

    public function removeServerKey($identifier): void
    {
        $this->ovh->delete("/cloud/project/instance/$identifier/sshkey");
    }

    public function deleteServer(int $identifier): void
    {
        $this->ovh->delete("/cloud/project/instance/$identifier");
    }

    public function powerOnServer(int $identifier): void
    {
        $this->ovh->post("/cloud/project/instance/$identifier/action/start");
    }

    public function powerOffServer(int $identifier): void
    {
        $this->ovh->post("/cloud/project/instance/$identifier/action/stop");
    }

    public function shutdownServer(int $identifier): void
    {
        $this->ovh->post("/cloud/project/instance/$identifier/action/shutdown");
    }

    public function rebootServer(int $identifier): void
    {
        $this->ovh->post("/cloud/project/instance/$identifier/action/reboot");
    }

    public function findAllSshkeys(): array
    {
        $result = $this->ovh->get('/cloud/project/sshkey');

        return $result;
    }
}
