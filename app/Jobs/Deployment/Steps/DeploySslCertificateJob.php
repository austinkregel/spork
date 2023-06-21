<?php

namespace App\Jobs\Deployment\Steps;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Server;
use App\Services\Development\ForgeDevelopmentService;

class DeploySslCertificateJob
{
    public function __construct(
        public Server $server,
        public Domain $domain,
        public Credential $credential
    ) {
    }

    public function handle()
    {
        // Configure the domain for the server
        //  DNS, Setup SSL, Verify

        // Register the domain to forge.
        $service = new ForgeDevelopmentService($this->credential);

        $site = $service->createDomainIfNotExists($this->domain, $this->server);

        $service->setupSslCertificate($this->domain, $this->server, $site);

        $service->setupSsl($this->domain, $this->server);
        dd(dns_get_record($this->domain->name, DNS_A));
    }
}
