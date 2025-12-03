<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Thread;

class RoomCanonicalAliasEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.canonical_alias';
    }

    public function handle(MatrixEventContext $context): void
    {
        if (! $context->roomId) {
            return;
        }

        $thread = Thread::firstWhere('thread_id', $context->roomId);

        if (! $thread) {
            return;
        }

        $alias = $context->event['content']['alias'] ?? null;

        $thread->update([
            'settings' => array_merge($thread->settings ?? [], [
                'canonical_alias' => $alias,
            ]),
        ]);
    }
}

