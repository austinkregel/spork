<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;

class CrossSigningEventHandler implements MatrixEventHandlerContract
{
    protected const MASTER_TYPE = 'm.cross_signing.master';
    protected const SELF_SIGNING_TYPE = 'm.cross_signing.self_signing';
    protected const USER_SIGNING_TYPE = 'm.cross_signing.user_signing';

    public function supports(string $eventType): bool
    {
        return in_array($eventType, [
            self::MASTER_TYPE,
            self::SELF_SIGNING_TYPE,
            self::USER_SIGNING_TYPE,
        ], true);
    }

    public function handle(MatrixEventContext $context): void
    {
        $payload = $context->event['content']['encrypted'] ?? [];

        if ($context->event['type'] === self::MASTER_TYPE) {
            $context->state->setMasterKey($payload);

            return;
        }

        if ($context->event['type'] === self::SELF_SIGNING_TYPE) {
            $context->state->mergeSelfSignKey($payload);

            return;
        }

        $context->state->mergeSigningUser($payload);
    }
}










