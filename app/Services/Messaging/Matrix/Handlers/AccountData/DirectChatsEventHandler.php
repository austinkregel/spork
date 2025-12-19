<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;

class DirectChatsEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'm.direct';
    }

    public function handle(MatrixEventContext $context): void
    {
        $context->state->setDms($context->event['content'] ?? []);
    }
}
















