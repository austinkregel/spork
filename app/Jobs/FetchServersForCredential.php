<?php

namespace App\Jobs;

use App\Jobs\Servers\DigitalOceanSyncJob;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchServersForCredential implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Credential $credential,
        public ?User $user
    ) {
        $this->user = $user ?? auth()->user();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Dispatcher $dispatcher)
    {
        if ($this->credential->type !== Credential::TYPE_SERVER) {
            return;
        }

        $dispatcher->dispatchSync(match ($this->credential->service) {
            Credential::DIGITAL_OCEAN => new DigitalOceanSyncJob($this->credential, $this->user),
        });
    }
}
