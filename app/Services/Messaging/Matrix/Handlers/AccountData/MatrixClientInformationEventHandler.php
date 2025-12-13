<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Services\Messaging\Matrix\MatrixEventSupport;

class MatrixClientInformationEventHandler implements MatrixEventHandlerContract
{
    public function __construct(
        protected MatrixEventSupport $support,
    ) {}

    public function supports(string $eventType): bool
    {
        return str_starts_with($eventType, 'io.element.matrix_client_information.');
    }

    public function handle(MatrixEventContext $context): void
    {
        $deviceId = $this->support->extractDeviceId($context->event['type']);
        $context->state->mergeDevice($deviceId, $context->event['content'] ?? []);
    }
}








