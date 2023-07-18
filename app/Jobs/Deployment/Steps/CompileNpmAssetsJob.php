<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Services\Development\ForgeDevelopmentService;
use App\Services\SshService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CompileNpmAssetsJob implements ShouldQueue
{
    use DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(
        public Server $server,
        public Domain $domain,
        public Project $project
    ) {
    }

    public function handle()
    {
        $sshCredential = $this->project->credentialFor(Credential::TYPE_SSH);
        // Run npm install, then npm run production
        // upload assets to s3

        try {
            $service = new SshService(
                $this->server->internal_ip_address,
                'forge',
                $sshCredential->getPublicKey(),
                $sshCredential->getPrivateKey()
            );  // replace with your server details

            $installLog = $service->execute('npm install', '/home/forge/'. $this->domain->name);
            $buildLog = $service->execute('npm run build', '/home/forge/'. $this->domain->name);


        } catch (\Exception $e) {
            dd($e);
        }
    }
}
