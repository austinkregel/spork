<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Services\SshService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompileNpmAssetsJob implements ShouldQueue
{
    use Batchable, DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Server $server,
        public Domain $domain,
        public Project $project
    ) {}

    public function handle(): void
    {
        $sshCredential = $this->project->credentialFor(Credential::TYPE_SSH);

        try {
            $service = new SshService(
                host: $this->server->internal_ip_address,
                username: 'forge',
                publicKeyFile: $sshCredential->getPublicKey(),
                privateKeyFile: $sshCredential->getPrivateKey(),
                passKey: $sshCredential->getPasskey()
            );  // replace with your server details

            $installLog = $service->execute('npm install', '/home/forge/'.$this->domain->name);
            $buildLog = $service->execute('npm run build', '/home/forge/'.$this->domain->name);

        } catch (\Exception $e) {
            dd($e);
        }
    }
}
