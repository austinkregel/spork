<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Services\Messaging\Matrix\MatrixEventSupport;

class IgnoredAccountEventHandler implements MatrixEventHandlerContract
{
    protected array $ignoredTypes = [
        'im.vector.analytics',
        'm.push_rules',
        'm.accepted_terms',
        'm.fully_read',
    ];

    public function __construct(
        protected MatrixEventSupport $support,
    ) {}

    public function supports(string $eventType): bool
    {
        return in_array($eventType, $this->ignoredTypes, true);
    }

    public function handle(MatrixEventContext $context): void
    {
        $this->support->ignored(
            $context->event,
            'configured_account_ignore',
            self::class
        );
    }
}
