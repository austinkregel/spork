<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Thread;
use App\Services\Messaging\Matrix\MatrixEventSupport;
use Carbon\Carbon;

class RoomMessageEventHandler implements MatrixEventHandlerContract
{
    public function __construct(
        protected MatrixEventSupport $support,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.message';
    }

    public function handle(MatrixEventContext $context): void
    {
        if (! $context->roomId || ! $context->user || ! $context->credential) {
            return;
        }

        /** @var Thread|null $thread */
        $thread = Thread::query()
            ->with('participants')
            ->firstWhere('thread_id', $context->roomId);

        if (! $thread) {
            return;
        }

        if ($this->handleRelatedEvent($context, $thread)) {
            return;
        }

        if (! isset($context->event['content']['body'])) {
            if ($this->handleRedactedMessage($context, $thread)) {
                return;
            }

            return;
        }

        $sender = $this->support->findOrCreatePerson($context->event['sender'], $context->user);
        $inReplyToEventId = $context->event['content']['m.relates_to']['m.in_reply_to']['event_id'] ?? null;
        $replyToMessageId = null;

        if ($inReplyToEventId) {
            $replyToMessage = $thread->messages()->firstWhere('event_id', $inReplyToEventId);
            if ($replyToMessage) {
                $replyToMessageId = $replyToMessage->id;
            }
        }

        $timestamp = Carbon::createFromFormat('U', (string) (int) round(($context->event['origin_server_ts'] ?? 0) / 1000));
        $message = $thread->messages()->firstOrNew(['event_id' => $context->event['event_id']]);

        $message->forceFill($this->buildMessageAttributes(
            context: $context,
            thread: $thread,
            senderId: $sender->id,
            timestamp: $timestamp,
            replyToMessageId: $replyToMessageId,
            replyToEventId: $inReplyToEventId
        ))->save();

        if ($timestamp->isAfter($thread->origin_server_ts)) {
            $thread->update(['origin_server_ts' => $timestamp]);
        }
    }

    protected function buildMessageAttributes(
        MatrixEventContext $context,
        Thread $thread,
        int $senderId,
        Carbon $timestamp,
        ?int $replyToMessageId,
        ?string $replyToEventId,
    ): array {
        $attributes = [
            'from_person' => $senderId,
            'to_person' => $context->user->id,
            'thread_id' => $thread->id,
            'type' => $context->event['type'],
            'originated_at' => $timestamp,
            'message' => $context->event['content']['body'],
            'event_id' => $context->event['event_id'],
            'html_message' => $context->event['content']['formatted_body'] ?? null,
            'credential_id' => $context->credential->id,
            'is_decrypted' => true,
            'reply_to_message_id' => $replyToMessageId,
            'reply_to_event_id' => $replyToEventId,
            'settings' => $context->event['content']['settings'] ?? null,
            'thumbnail_url' => null,
        ];

        if (isset($context->event['content']['info']) && ($context->event['content']['msgtype'] ?? null) === 'm.image') {
            $attributes['thumbnail_url'] = $this->support->downloadMedia($context->credential, $context->event['content']['url']);
        }

        return $attributes;
    }

    protected function handleRelatedEvent(MatrixEventContext $context, Thread $thread): bool
    {
        $relation = $context->event['content']['m.relates_to'] ?? null;

        if (! $relation) {
            // If it is a message, a related event is not required.
            return false;
        }

        if (empty($relation['event_id'])) {
            return false;
        }

        $relatedEvent = $thread->messages()->firstWhere('event_id', $relation['event_id']);

        if (! $relatedEvent) {
            return false;
        }

        if (($relation['rel_type'] ?? null) !== 'm.replace') {
            return false;
        }

        $timestamp = Carbon::createFromFormat('U', (string) (int) round(($context->event['origin_server_ts'] ?? 0) / 1000));

        $relatedEvent->update([
            'message' => $context->event['content']['m.new_content']['body'] ?? $relatedEvent->message,
            'originated_at' => $timestamp,
        ]);

        return true;
    }

    protected function handleRedactedMessage(MatrixEventContext $context, Thread $thread): bool
    {
        $redaction = $context->event['unsigned']['redacted_because'] ?? null;

        if (! $redaction) {
            return false;
        }

        $target = $redaction['redacts'] ?? null;

        if (! $target) {
            $this->support->ignored(
                $context->event,
                'redaction_missing_target',
                self::class,
                ['room' => $context->roomId]
            );

            return true;
        }

        $relatedEvent = $thread->messages()->firstWhere('event_id', $target);

        if (! $relatedEvent) {
            $this->support->ignored(
                $context->event,
                'redaction_target_not_found',
                self::class,
                ['room' => $context->roomId]
            );

            return true;
        }

        $payload = $redaction;

        if (! isset($payload['origin_server_ts']) && isset($context->event['origin_server_ts'])) {
            $payload['origin_server_ts'] = $context->event['origin_server_ts'];
        }

        $this->support->redactMessage($payload);

        return true;
    }
}
