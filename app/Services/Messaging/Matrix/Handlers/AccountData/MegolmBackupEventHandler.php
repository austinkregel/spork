<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;

class MegolmBackupEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'm.megolm_backup.v1';
    }

    public function handle(MatrixEventContext $context): void
    {
        $context->state->mergeMegolmBackup($context->event['content']['encrypted'] ?? []);
    }
}
















