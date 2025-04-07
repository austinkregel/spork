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

class SetupLoadBalancerJob implements ShouldQueue
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
        // Register the domain to forge.
        $forgeCredential = $this->project->credentialFor(Credential::FORGE_DEVELOPMENT);

        $service = new ForgeDevelopmentService($forgeCredential);

        /** @var Site $site */
        $site = $service->createDomainIfNotExists(
            $this->domain,
            $this->project->domains->filter(fn (Domain $domain) => $domain->id !== $this->domain->id)->values(),
            $this->server
        );

        $service->updateLoadBalancer($this->domain, $this->project, (array) $site);
    }
}
