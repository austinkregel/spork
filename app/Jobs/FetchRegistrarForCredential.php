<?php

namespace App\Jobs;

use App\Jobs\Registrar\CloudflareSyncJob;
use App\Jobs\Registrar\NamecheapSyncJob;
use App\Models\Credential;
use App\Models\User;
use App\Services\Factories\RegistrarServiceFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchRegistrarForCredential implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Credential $credential,
        public ?User $user = null
    )
    {
        $this->user = $user ?? auth()->user();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Dispatcher $dispatcher)
    {
        if ($this->credential->type !== Credential::TYPE_REGISTRAR) {
            info('Credential is not of registrar type.');
            return;
        }

        $dispatcher->dispatchSync(match ($this->credential->service) {
            Credential::NAMECHEAP => new NamecheapSyncJob($this->credential, $this->user),
            Credential::CLOUDFLARE => new CloudflareSyncJob($this->credential, $this->user),
        });
    }
}
