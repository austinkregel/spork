<?php

namespace App\Jobs\Deployment\Steps;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Server;
use App\Services\Development\ForgeDevelopmentService;
use App\Services\Factories\DomainServiceFactory;
use Illuminate\Support\Collection;

class SetupLoadBalancerJob
{
    public function __construct(
        public Server $server,
        public Domain $domain,
        public Collection $domains,
        public Credential $credential
    ) {
    }

    public function handle()
    {
        // Configure the domain for the server
        // Register the domain to forge.
        $service = new ForgeDevelopmentService($this->credential);
        $domains = collect([]);

        $site = $service->createDomainIfNotExists($this->domain, $domains, $this->server);

        $service->setupSslCertificate($this->domain, $domains, $this->server, $site);
    }
}
