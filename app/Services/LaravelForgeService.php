<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Credential;
use GuzzleHttp\Client;
use Laravel\Forge\Forge;

class LaravelForgeService
{
    public Forge $client;

    public function __construct(
        public Credential $credential,
    ) {
        $this->client = new Forge($this->credential->access_token, new Client([
            'base_uri' => 'https://forge.laravel.com/api/v1/',
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer '.$this->credential->access_token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]));
    }

    public function getServers()
    {
        $servers = $this->client->get('https://forge.laravel.com/api/v1/servers');

        return array_map(fn ($serverConfig) => [
            'id' => $serverConfig['id'],
            'name' => $serverConfig['name'],
            'size' => $serverConfig['size'],
            'ip_address' => $serverConfig['ip_address'],
            'private_ip_address' => $serverConfig['private_ip_address'],
            'ssh_port' => $serverConfig['ssh_port'],
            'network' => $serverConfig['network'],
            'php_version' => $serverConfig['php_version'],
            'type' => $serverConfig['type'],
            'created_at' => $serverConfig['created_at'],
        ], $servers['servers']);
    }

    public function getDomains($serverId)
    {
        $servers = $this->client->get("https://forge.laravel.com/api/v1/servers/{$serverId}/sites");

        dd($servers);
    }
}
