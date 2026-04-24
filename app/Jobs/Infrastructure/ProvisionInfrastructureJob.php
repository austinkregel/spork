<?php

declare(strict_types=1);

namespace App\Jobs\Infrastructure;

use App\Models\InfrastructureProvisionRequest;
use App\Services\Infrastructure\Provisioning\InfrastructureProvisionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionInfrastructureJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $provisionRequestId) {}

    public function handle(InfrastructureProvisionManager $manager): void
    {
        $provisionRequest = InfrastructureProvisionRequest::query()->find($this->provisionRequestId);

        if (! $provisionRequest || $provisionRequest->status === 'completed') {
            return;
        }

        $provisionRequest->update(['status' => 'provisioning']);

        try {
            $result = $manager->handle($provisionRequest);

            $provisionRequest->update([
                'status' => 'completed',
                'result' => $result,
                'server_id' => $result['server']['id'] ?? null,
                'domain_id' => $result['domain']['id'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            $provisionRequest->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
