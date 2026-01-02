<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;

class WebSettingsEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'im.vector.web.settings';
    }

    public function handle(MatrixEventContext $context): void
    {
        $context->state->setClient($context->event['content'] ?? []);
    }
}




















