<?php

namespace App\Jobs\Deployment\Steps;

use App\Jobs\Domains\NameServerVerificationJob;
use App\Models\Credential;
use App\Models\Domain;
use App\Services\Factories\DomainServiceFactory;
use App\Services\Factories\RegistrarServiceFactory;
use App\Services\Registrar\Domain\CloudflareService;

class SetupCloudflareDns
{
    public function __construct(
        public Domain $domain,
        public Credential $cloudflareDns,
        public Credential $registrar,
    ) {
    }

    public function handle(DomainServiceFactory $factory)
    {
        // Check with our DNS service to see if our domain already exists there.

        $service = $factory->make($this->cloudflareDns);
        $registrar = (new RegistrarServiceFactory)->make($this->registrar);

        $page = 1;
        do {
            $domainPaginator = $service->getDomains(100, $page++);
            foreach ($domainPaginator->items() as $domain) {
                if ($domain['domain'] === $this->domain->name) {
                    $registrar->updateDomainNs($this->domain->name, $domain['']);
                    break 2;
                }
            }
        } while ($domainPaginator->hasMorePages());

        $servers = $service->createDomain($this->domain->name);

        $registrar->updateDomainNs($this->domain->name, $servers);

        dispatch(new NameServerVerificationJob($this->domain->name, $servers));
    }
}
