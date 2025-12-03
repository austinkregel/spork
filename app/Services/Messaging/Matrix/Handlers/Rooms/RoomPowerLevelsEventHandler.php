<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Thread;

class RoomPowerLevelsEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.power_levels';
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

        $thread->update([
            'settings' => array_merge($thread->settings ?? [], [
                'power_levels' => $context->event['content'] ?? [],
            ]),
        ]);
    }
}

