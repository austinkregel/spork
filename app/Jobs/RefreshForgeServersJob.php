<?php

namespace App\Jobs;

use App\Models\Credential;
use App\Models\Server;
use App\Services\LaravelForgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshForgeServersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Credential $credential,
        public ?LaravelForgeService $service
    ) {
        $this->service = new LaravelForgeService($credential);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $servers = $this->service->getServers();

        foreach ($servers as $server) {
            //            $localServer = Server::where()
        }
    }
}
