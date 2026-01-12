<?php

declare(strict_types=1);

namespace App\Services\Infrastructure\Provisioning;

use App\Models\InfrastructureProvisionRequest;

class InfrastructureProvisionManager
{
    public function __construct(
        private readonly ServerProvisioningService $serverProvisioningService,
        private readonly ProvisioningDomainService $provisioningDomainService,
    ) {}

    public function handle(InfrastructureProvisionRequest $request): array
    {
        $computeResult = $this->serverProvisioningService->handle($request);

        $domainResult = $this->provisioningDomainService->handle($request, $computeResult);

        return [
            'server' => $computeResult,
            'domain' => $domainResult,
        ];
    }
}
