<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Servers\DigitalOceanSyncJob;
use App\Jobs\Servers\LaravelForgeServersSyncJob;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchResourcesFromCredentials implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(

    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Dispatcher $dispatcher)
    {
        $credentials = Credential::all();

        foreach ($credentials as $credential) {
            if ($credential->type === 'ssh') {
                continue;
            }

            $dispatcher->dispatchSync(match ($credential->type) {
                Credential::TYPE_REGISTRAR => new FetchRegistrarForCredential($credential),
                Credential::TYPE_DOMAIN => new FetchDomainsForCredential($credential),
                Credential::TYPE_SERVER => new FetchServersForCredential($credential),
                Credential::TYPE_DEVELOPMENT, 'forge' => new LaravelForgeServersSyncJob($credential),
            });
        }
    }
}
