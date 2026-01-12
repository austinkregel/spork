<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Deployment\Steps\SetupCloudflareDns;
use App\Models\Credential;
use App\Models\Project;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class RunDeployment implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Project $project) {}

    public function handle(): void
    {
        $servers = $this->project->servers;
        $domains = $this->project->domains;

        $jobsByDomain = [];
        foreach ($domains as $domain) {
            if ($domain->cloudflare_id === null) {
                continue;
            }
            if (! isset($jobsByDomain[$domain->name])) {
                $jobsByDomain[$domain->name] = [];
            }
            $jobsByDomain[$domain->name][] = new SetupCloudflareDns($domain, $this->project->credentialFor(Credential::CLOUDFLARE), $this->project->credentialFor(Credential::NAMECHEAP));
        }

        $primaryDomain = $domains->first();
        $otherDomains = $domains->slice(1);
        // Forge-based server deployment jobs have been removed.

        Bus::batch(array_values(array_filter($jobsByDomain)))->then(function (Batch $batch) {
            echo 'Done";';
        })->dispatch();
    }
}
