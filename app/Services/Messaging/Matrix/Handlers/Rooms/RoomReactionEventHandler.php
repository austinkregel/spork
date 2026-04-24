<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\MessageReaction;
use App\Models\Thread;
use App\Services\Messaging\Matrix\MatrixEventSupport;

class RoomReactionEventHandler implements MatrixEventHandlerContract
{
    public function __construct(
        protected MatrixEventSupport $support,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'm.reaction';
    }

    public function handle(MatrixEventContext $context): void
    {
        if (! $context->roomId || ! $context->user) {
            return;
        }

        $relation = $context->event['content']['m.relates_to'] ?? null;

        if (($relation['rel_type'] ?? null) !== 'm.annotation') {
            $this->support->ignored($context->event, 'reaction_missing_annotation', self::class, ['room' => $context->roomId]);

            return;
        }

        $targetEventId = $relation['event_id'] ?? null;
        $emoji = $relation['key'] ?? null;

        if (! $targetEventId || ! $emoji) {
            $this->support->ignored($context->event, 'reaction_missing_target', self::class, ['room' => $context->roomId]);

            return;
        }

        /** @var Thread|null $thread */
        $thread = Thread::query()->firstWhere('thread_id', $context->roomId);

        if (! $thread) {
            return;
        }

        $message = $thread->messages()->firstWhere('event_id', $targetEventId);

        if (! $message) {
            $this->support->ignored($context->event, 'reaction_target_not_found', self::class, ['room' => $context->roomId]);

            return;
        }

        $sender = $this->support->findOrCreatePerson($context->event['sender'], $context->user);

        MessageReaction::query()->updateOrCreate(
            [
                'message_id' => $message->id,
                'person_id' => $sender->id,
                'emoji' => $emoji,
            ],
            [
                'sender_identifier' => $context->event['sender'] ?? null,
                'matrix_event_id' => $context->event['event_id'],
                'payload' => $context->event['content'] ?? [],
            ]
        );
    }
}
