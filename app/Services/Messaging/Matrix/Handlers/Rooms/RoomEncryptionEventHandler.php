<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Thread;

class RoomEncryptionEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.encryption';
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
            'settings' => array_merge(
                $thread->settings ?? [],
                [
                    'encrypted' => true,
                    'algorithm' => $context->event['content']['algorithm'] ?? null,
                    'rotation_period_ms' => $context->event['content']['rotation_period_ms'] ?? null,
                ]
            ),
        ]);
    }
}




















