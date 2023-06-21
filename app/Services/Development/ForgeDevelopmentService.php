<?php

namespace App\Services\Development;

use App\Contracts\Services\DomainServiceContract;
use App\Contracts\Services\ServerServiceContract;
use App\Models\Credential;
use Illuminate\Support\Collection;
use Laravel\Forge\Exceptions\ValidationException;
use Laravel\Forge\Forge;

class ForgeDevelopmentService
{
    public Forge $client;

    public function __construct(
        public Credential $credential,
    ) {
        $this->client = new Forge($this->credential->access_token);
    }

    public function findAllServers()
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

    public function createDomainIfNotExists(\App\Models\Domain $domain, Collection $domains, \App\Models\Server $server)
    {
        $sites = $this->client->get("https://forge.laravel.com/api/v1/servers/$server->server_id/sites");

        foreach ($sites['sites'] as $site) {
            if ($domain->name === $site['name']) {
                // The domain already exists on the server.
                return $site;
            }
        }

        try {

            $site = $this->client->post("https://forge.laravel.com/api/v1/servers/$server->server_id/sites", [
                'domain' => $domain->name,
                'project_type' => 'php',
                'aliases' => $domains->map(fn($domain) => $domain->name)->toArray(),
                'directory' => 'public',
                'isolated' => false,
            ]);
        } catch (ValidationException $e) {
            dd($e->errors());
        }
        /**
         * {
         *       "id": 2,
         *       "name": "site.com",
         *       "aliases": [
         *           "alias1.com",
         *           "alias2.com"
         *       ],
         *       "directory": "/test",
         *       "wildcards": false,
         *       "isolated": true,
         *       "username": "forge",
         *       "status": "installing",
         *       "repository": null,
         *       "repository_provider": null,
         *       "repository_branch": null,
         *       "repository_status": null,
         *       "quick_deploy": false,
         *       "project_type": "php",
         *       "app": null,
         *       "php_version": "php81",
         *       "app_status": null,
         *       "slack_channel": null,
         *       "telegram_chat_id": null,
         *       "telegram_chat_title": null,
         *       "created_at": "2016-12-16 16:38:08",
         *       "deployment_url": "...",
         *       "tags": []
         *   }
         */
        return $site['site'];
    }


    public function setupSslCertificate(\App\Models\Domain $domain, Collection $domains, \App\Models\Server $server, array $site)
    {
        $certificates = $this->client->get("https://forge.laravel.com/api/v1/servers/{$server->server_id}/sites/". $site['id']. '/certificates');

        foreach ($certificates['certificates'] as $certificate) {
            if ($certificate['active']) {
                return $certificate;
            }
        }

        $data = [
            'domains' => $domains->map(fn($d) => $d->name)->concat([$domain->name])->toArray(),
            'type' => 'cloudflare',
            'dns_provider' => [
                // TODO: Come back to this and use a better credential form.
                'cloudflare_api_token' => Credential::where('service', 'cloudflare')->first()->access_token
            ]
        ];

        try {
            $certificate = $this->client->post("https://forge.laravel.com/api/v1/servers/{$server->server_id}/sites/" . $site['id'] . '/certificates/letsencrypt', $data);
        } catch (ValidationException $e) {
            dd($e->errors(), $data);
        }
        /**
         * We've got
         * {
         *     "domain": "domain.com",
         *     "request_status": "creating",
         *     "created_at": "2016-12-17 07:02:35",
         *     "id": 3,
         *     "existing": false,
         *     "active": false
         *   }
         */
        return $certificate['certificate'];
    }

}
