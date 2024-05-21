<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class CompileAndUploadAssetsToStorage implements ShouldQueue
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
        Bus::chain([
            new InstallComposerAssetsJob($this->server, $this->domain, $this->project),
            new CompileNpmAssetsJob($this->server, $this->domain, $this->project),
            new UploadAssetsToStorageJob($this->server, $this->domain, $this->project),
        ])->dispatch();
    }
}
