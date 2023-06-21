<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Project $project,
    ) {
    }

    public function handle()
    {
        $this->project->load('servers.tags', 'domains');
        // .... Do stuff?...
        $this->project->domains->each(function (Domain $domain) {
            $this->project->servers->each(function (Server $server) {

            });
        });
        // Connect to related servers
        // Ensure there is routing between servers
        // perform various actions for it based on the tags of the servers.
        // NPM install if needed
        // composer install if needed
        // one server should run migrations
        // restart horizon on the jobs server
        // publish vendor assets
        // clean up unneeded files
    }
}
