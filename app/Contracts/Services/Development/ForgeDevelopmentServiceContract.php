<?php

declare(strict_types=1);

namespace App\Contracts\Services\Development;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Collection;

interface ForgeDevelopmentServiceContract
{
    public function findAllServers(): array;

    public function getDomains($serverId): void;

    public function createDomainIfNotExists(Domain $domain, Collection $domains, Server $server);

    public function setupSslCertificate(Domain $domain, Collection $domains, Server $server, array $site);

    public function createDaemonIfNotExists(Domain $domain, Server $server, array $daemon);

    public function createCronIfNotExists(Domain $domain, Server $server, array $cron);

    public function createRedirectIfNotExists(Domain $domain, Server $server, array $redirect);

    public function createDeploymentScriptIfNotExists(Domain $domain, Server $server, array $script);

    public function updateLoadBalancer(Domain $domain, Project $project, array $site): void;

    public function addSSHKeyToServer(Server $server, Credential $credential): void;
}

