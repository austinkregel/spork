<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Jobs\Domains\VerifyDnsValue;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\Server;
use App\Services\Factories\DomainServiceFactory;
use Illuminate\Support\Collection;

class SetupLoadBalancerDnsRecordJob
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
        $dnsService = (new DomainServiceFactory)->make(Credential::find(4));

        $dnsService->createDnsRecord($this->domain, [
            'type' => 'A',
            'name' => '@',
            'content' => $this->server->ip_address,
            'ttl' => 'auto',
        ]);

        dispatch(new VerifyDnsValue($this->domain->name, 'A', $this->server->ip_address));
    }
}
