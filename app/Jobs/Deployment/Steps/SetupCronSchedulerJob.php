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
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetupCronSchedulerJob implements ShouldQueue
{
    use Batchable, DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Server $server,
        public Domain $domain,
        public Project $project
    ) {
    }

    public function handle(): void
    {
        // Configure the domain for the server
        // Register the domain to forge.
        $forgeCredential = $this->project->credentialFor(Credential::FORGE_DEVELOPMENT);

        $laravelForgeService = new ForgeDevelopmentService($forgeCredential);

        $laravelForgeService->createDomainIfNotExists(
            $this->domain,
            $this->project->domains->filter(fn (Domain $domain) => $domain->id !== $this->domain->id)->values(),
            $this->server
        );

        $laravelForgeService->createCronIfNotExists(
            $this->domain,
            $this->server,
            [
                'command' => 'php /home/forge/'.$this->domain->name.'/artisan schedule:run',
                'frequency' => 'minutely',
                'user' => 'forge',
                'minute' => '*',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ]
        );
    }
}
