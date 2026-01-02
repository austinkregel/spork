<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;

class MatrixEventHandlerRegistry
{
    /**
     * @param MatrixEventHandlerContract[] $handlers
     */
    public function __construct(
        protected iterable $handlers,
    ) {}

    public function dispatch(MatrixEventContext $context): bool
    {
        $eventType = $context->event['type'] ?? null;

        if (! $eventType) {
            return false;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($eventType)) {
                $handler->handle($context);

                return true;
            }
        }

        return false;
    }
}




















