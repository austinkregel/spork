<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Collection;
use Laravel\Forge\Exceptions\ValidationException;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Certificate;
use Laravel\Forge\Resources\Job;

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
        $servers = $this->client->servers();

        return array_map(fn (\Laravel\Forge\Resources\Server $serverConfig) => [
            'id' => $serverConfig->id,
            'name' => $serverConfig->name,
            'size' => $serverConfig->size,
            'ip_address' => $serverConfig->ipAddress,
            'private_ip_address' => $serverConfig->privateIpAddress,
            'ssh_port' => $serverConfig->sshPort,
            'network' => $serverConfig->network,
            'php_version' => $serverConfig->phpVersion,
            'type' => $serverConfig->type,
            'created_at' => $serverConfig->createdAt,
        ], $servers);
    }

    public function getDomains($serverId)
    {
        $servers = $this->client->get("https://forge.laravel.com/api/v1/servers/{$serverId}/sites");

        dd($servers);
    }

    public function createDomainIfNotExists(Domain $domain, Collection $domains, Server $server)
    {
        $sites = $this->client->sites($server->server_id);

        foreach ($sites as $site) {
            if ($domain->name === $site->name) {
                // The domain already exists on the server.
                return $site;
            }
        }

        try {
            $site = $this->client->createSite($server->server_id, [
                'domain' => $domain->name,
                'project_type' => 'php',
                'aliases' => $domains->map(fn ($domain) => $domain->name)->toArray(),
                'directory' => 'public',
                'isolated' => false,
            ], true);
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

    public function setupSslCertificate(Domain $domain, Collection $domains, Server $server, array $site)
    {
        $certificates = $this->client->certificates($server->server_id, $site['id']);

        /** @var Certificate $certificate */
        foreach ($certificates as $certificate) {
            if ($certificate->active) {
                return $certificate;
            }
        }

        $allDomains = $domains->map(fn ($d) => $d->name)->concat([$domain->name]);
        $data = [
            'domains' => $allDomains->concat($allDomains->map(fn ($name) => '*.'.$name))->toArray(),
            'type' => 'cloudflare',
            'dns_provider' => [
                'type' => 'cloudflare',
                // TODO: Come back to this and use a better credential form.
                'cloudflare_api_token' => Credential::where('service', 'cloudflare')->first()->access_token,
            ],
        ];

        try {
            $certificate = $this->client->obtainLetsEncryptCertificate($server->server_id, $site['id'], $data, true);
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

    public function createDaemonIfNotExists(Domain $domain, Server $server, array $daemon)
    {

        $servers = $this->client->get("https://forge.laravel.com/api/v1/servers/{$server->server_id}/daemons");

        foreach ($servers['daemons'] as $existingDaemon) {
            if ($existingDaemon['command'] === $daemon['command']) {
                return $existingDaemon;
            }
        }

        try {
            $daemon = $this->client->createDaemon($server->server_id, $daemon, true);
        } catch (ValidationException $e) {
            dd($e->errors());
        }

        return $daemon['daemon'];
    }

    public function createCronIfNotExists(Domain $domain, Server $server, array $cron)
    {
        $servers = $this->client->jobs($server->server_id);

        /** @var Job $existingCron */
        foreach ($servers['cron'] as $existingCron) {
            if ($existingCron->command === $cron['command']) {
                return $existingCron;
            }
        }

        try {
            $this->client->createJob($server->server_id, $cron, true);
        } catch (ValidationException $e) {
            dd($e->errors());
        }

        return $cron['cron'];
    }

    // create a redirect in laravel forge if there isn't already an existing redirect
    public function createRedirectIfNotExists(Domain $domain, Server $server, array $redirect)
    {
        $servers = $this->client->get("https://forge.laravel.com/api/v1/servers/{$server->server_id}/redirects");

        foreach ($servers['redirects'] as $existingRedirect) {
            if ($existingRedirect['from'] === $redirect['from']) {
                return $existingRedirect;
            }
        }

        try {
            $redirect = $this->client->createRedirectRule($server->server_id, $domain->domain_id, $redirect, true);
        } catch (ValidationException $e) {
            dd($e->errors());
        }

        return $redirect['redirect'];
    }

    public function createDeploymentScriptIfNotExists(Domain $domain, Server $server, array $script)
    {
        $servers = $this->client->get("https://forge.laravel.com/api/v1/servers/{$server->server_id}/sites/{$domain->forge_site_id}/deployment/script");

        if ($servers['script'] === $script['script']) {
            return $servers['script'];
        }

        try {
            $script = $this->client->put("https://forge.laravel.com/api/v1/servers/{$server->server_id}/sites/{$domain->forge_site_id}/deployment/script", $script);
        } catch (ValidationException $e) {
            dd($e->errors());
        }

        return $script['script'];
    }

    protected function getWeight(Server $server)
    {
        $tags = $server->tags->map(fn ($tag) => $tag->name);
        if ($tags->contains('app')) {
            return 1;
        }
        if ($tags->contains('web')) {
            return 5;
        }

        return 10;
    }

    protected function getBackup(
        Server $server,
        $appServers,
        $webServers,
        $jobServers
    ) {
        $tags = $server->tags->map(fn ($tag) => $tag->name);
        if ($tags->contains('jobs')) {
            // Job servers should only ever serve HTTP requests if the app servers are down.
            return true;
        }

        if ($appServers->count() > 0 && $webServers->count() > 0) {
            // If there are app servers and web servers, then we can assume that the app servers will be able to serve
            // requests if the web servers are down.
            return $tags->contains('web');
        }

        return false;
    }

    public function updateLoadBalancer(Domain $domain, Project $project, array $site)
    {
        $servers = $project->servers()->with('tags')->get();

        $loadBalancingServer = $project->servers()->with('tags')->whereHas('tags', fn ($q) => $q->where('name->en', 'loadbalancer'))->first();
        $appServers = $project->servers()->with('tags')->whereHas('tags', fn ($q) => $q->where('name->en', 'app'))->get();
        $webServers = $project->servers()->with('tags')->whereHas('tags', fn ($q) => $q->where('name->en', 'web'))->get();
        $jobServers = $project->servers()->with('tags')->whereHas('tags', fn ($q) => $q->where('name->en', 'jobs'))->get();

        $mapFunction = fn (Server $server) => [
            'id' => $server->server_id,
            'weight' => $this->getWeight($server),
            'backup' => $this->getBackup(
                server: $server,
                appServers: $appServers,
                webServers: $webServers,
                jobServers: $jobServers,
            ),
            'down' => 0,
            'port' => 80,
        ];

        try {
            $this->client->put("https://forge.laravel.com/api/v1/servers/{$loadBalancingServer->server_id}/sites/{$site['id']}/balancing", [
                'json' => [
                    'servers' => $appServers->map($mapFunction)
                        ->concat($webServers->map($mapFunction))
                        ->concat($jobServers->map($mapFunction))
                        ->toArray(),
                    'method' => 'least_conn',
                ],
            ]);
        } catch (ValidationException $e) {
            dd($e->errors());
        }
    }

    public function addSSHKeyToServer(Server $server, Credential $credential)
    {
        $serverKeys = $this->client->keys($server->server_id);

        foreach ($serverKeys as $key) {
            if ($key->name === 'Reforged Spork - Automation SSH Key') {
                return;
            }
        }

        $this->client->createSSHKey($server->server_id, [
            'name' => 'Reforged Spork - Automation SSH Key',
            'key' => $credential->settings['pub_key'],
            'username' => 'forge',
        ], true);
    }
}
