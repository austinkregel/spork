<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Services\Development\ForgeDevelopmentService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Resources\Site;

class DeploySslCertificateJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Server $server,
        public Domain $domain,
        public Project $project
    ) {}

    public function handle(): void
    {
        // Configure the domain for the server
        //  DNS, Setup SSL, Verify
        // Register the domain to forge.
        $forgeCredential = $this->project->credentialFor(Credential::FORGE_DEVELOPMENT);
        $service = new ForgeDevelopmentService($forgeCredential);
        $domainAliases = $this->project->domains->filter(fn (Domain $domain) => $domain->id !== $this->domain->id)->values();

        /** @var Site $site */
        $site = $service->createDomainIfNotExists($this->domain, $domainAliases, $this->server);

        if ($this->aRecordForOurServerDoesntAlreadyExist($this->domain, $this->server, (array) $site)) {
            // If we dont have all our servers pointing at the server in question fetching an SSL cert will fail
            return;
        }

        $service->setupSslCertificate($this->domain, $domainAliases, $this->server, (array) $site);
    }

    protected function aRecordForOurServerDoesntAlreadyExist(Domain $domain, Server $server, array $site): bool
    {
        $aRecordValues = array_map(fn (array $record) => $record['ip'], dns_get_record($domain->name, DNS_A));

        return ! in_array($server->ip_address, $aRecordValues);
    }
}
