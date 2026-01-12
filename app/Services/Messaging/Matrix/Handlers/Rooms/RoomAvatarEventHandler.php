<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Thread;
use App\Services\Messaging\Matrix\MatrixEventSupport;

class RoomAvatarEventHandler implements MatrixEventHandlerContract
{
    public function __construct(
        protected MatrixEventSupport $support,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.avatar';
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

        $avatarUrl = $context->event['content']['url'] ?? null;

        if (! $avatarUrl) {
            $thread->update([
                'settings' => array_merge($thread->settings ?? [], [
                    'avatar_url' => null,
                ]),
            ]);

            return;
        }

        if (! $context->credential) {
            $this->support->ignored(
                $context->event,
                'avatar_without_credential',
                self::class,
                ['room' => $context->roomId]
            );

            return;
        }

        $downloaded = $this->support->downloadMedia($context->credential, $avatarUrl);

        $thread->update([
            'settings' => array_merge($thread->settings ?? [], [
                'avatar_url' => $downloaded ?? $avatarUrl,
            ]),
        ]);
    }
}
