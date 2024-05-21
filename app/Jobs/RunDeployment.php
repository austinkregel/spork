<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Deployment\Steps\AddSSHKeyToServerJob;
use App\Jobs\Deployment\Steps\CompileNpmAssetsJob;
use App\Jobs\Deployment\Steps\DeploySslCertificateJob;
use App\Jobs\Deployment\Steps\SetupCloudflareDns;
use App\Jobs\Deployment\Steps\SetupCronSchedulerJob;
use App\Jobs\Deployment\Steps\SetupHorizonSchedulerJob;
use App\Jobs\Deployment\Steps\SetupLoadBalancerDnsRecordJob;
use App\Jobs\Deployment\Steps\SetupLoadBalancerJob;
use App\Jobs\Deployment\Steps\SetupWebServerJob;
use App\Models\Credential;
use App\Models\Project;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class RunDeployment implements ShouldQueue
{
    use Batchable, DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Project $project)
    {
    }

    public function handle(): void
    {
        $servers = $this->project->servers;
        $domains = $this->project->domains;

        $jobs = [];
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
        foreach ($servers as $server) {
            $serverJobs = [];
            if (! isset($jobsByDomain[$primaryDomain->name])) {
                $jobsByDomain[$primaryDomain->name] = [];
            }
            $tags = $server->tags->map->name;

            $serverJobs[] = new AddSSHKeyToServerJob($server, $this->project->credentialFor(Credential::TYPE_SSH), $this->project->credentialFor(Credential::FORGE_DEVELOPMENT));

            if ($tags->contains('loadbalancer')) {
                $serverJobs[] = new SetupLoadBalancerDnsRecordJob($server, $primaryDomain, $this->project);
                $serverJobs[] = new SetupLoadBalancerJob($server, $primaryDomain, $this->project);
                $serverJobs[] = new DeploySslCertificateJob($server, $primaryDomain, $this->project);
            }

            if ($tags->contains('app') || $tags->contains('web')) {
                //                $serverJobs[] = new CompileNpmAssetsJob($server, $primaryDomain, $this->project);
                $serverJobs[] = new SetupWebServerJob($server, $primaryDomain, $this->project);
                //                $serverJobs[] = new UploadAssetsJob($server);
            }

            if ($tags->contains('jobs')) {
                $serverJobs[] = new SetupCronSchedulerJob($server, $primaryDomain, $this->project);
                $serverJobs[] = new SetupHorizonSchedulerJob($server, $primaryDomain, $this->project);
            }

            //                if ($server->tags->contains('database')) {
            //                    $serverJobs[] = new CreateDatabaseJob($server, $this->project);
            //                }

            array_push($jobsByDomain[$primaryDomain->name], ...$serverJobs);
        }

        Bus::batch(array_values(array_filter($jobsByDomain)))->then(function (Batch $batch) {
            echo 'Done";';
        })->dispatch();
    }
}
