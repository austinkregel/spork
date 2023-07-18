<?php

namespace App\Jobs\Deployment\Steps;

use App\Models\Credential;
use App\Models\Server;
use App\Services\Development\ForgeDevelopmentService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddSSHKeyToServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Server $server,
        public Credential $credential,
        public Credential $forgeCredential,
    )    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $laravelForgeService = new ForgeDevelopmentService($this->forgeCredential);

        $laravelForgeService->addSSHKeyToServer($this->server, $this->credential);
    }
}
