<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Models\Credential;
use App\Repositories\MatrixClientSyncRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class MatrixSyncJob implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(CredentialRepositoryContract $credentialRepository, MatrixClientSyncRepository $repository): void
    {
        /** @var Credential $credential */
        $credential = collect($credentialRepository->findAllOfType(Credential::TYPE_MATRIX)->items())->first();

        $nextBatch = $credential->settings['next_batch'] ?? null;

        $rooms = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$credential->access_token,
        ])
            ->get($credential->settings['matrix_server'].'/_matrix/client/v3/sync', [
                'since' => $nextBatch,
                'timeout' => 30000,
            ])
            ->json();
        $nextBatch = $rooms['next_batch'];

        $repository->process($rooms, $credential, $credential->user);

        $credential->update([
            'settings' => array_merge($credential->settings, [
                'next_batch' => $nextBatch,
            ]),
        ]);
    }
}
