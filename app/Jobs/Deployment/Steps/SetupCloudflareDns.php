<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Jobs\Domains\NameServerVerificationJob;
use App\Models\Credential;
use App\Models\Domain;
use App\Services\Factories\DomainServiceFactory;
use App\Services\Factories\RegistrarServiceFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetupCloudflareDns implements ShouldQueue
{
    use DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels, Batchable;

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
                    //                    $registrar->updateDomainNs($this->domain->name, $domain['name_servers']);
                    break 2;
                }
            }
        } while ($domainPaginator->hasMorePages());

        /// Create domain if not exists
        $servers = $service->createDomain($this->domain->name);

        // Update the NS with registrar if not already set..
        $registrar->updateDomainNs($this->domain->name, $servers);

        dispatch(new NameServerVerificationJob($this->domain->name, $servers));
    }
}
