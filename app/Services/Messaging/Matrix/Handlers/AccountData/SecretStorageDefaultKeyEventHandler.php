<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;

class SecretStorageDefaultKeyEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'm.secret_storage.default_key';
    }

    public function handle(MatrixEventContext $context): void
    {
        $key = $context->event['content']['key'] ?? null;

        if (! $key) {
            return;
        }

        $context->state->setDefaultKey($key);
    }
}




















