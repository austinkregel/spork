<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Credential;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class FetchResourcesFromCredentials implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(QueueingDispatcher $dispatcher)
    {
        $credentials = Credential::all();

        $jobs = $credentials->groupBy('user_id')
            ->map(fn (Collection $group) => $group->map(fn ($credential) => new FetchResourcesFromCredential($credential))->toArray())
            ->toArray();

        $dispatcher->batch($jobs)
            ->name('Updatch Resources From Credentials')
            ->allowFailures()
            ->dispatch();
    }
}
