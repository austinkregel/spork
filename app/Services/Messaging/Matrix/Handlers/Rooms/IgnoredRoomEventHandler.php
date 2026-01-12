<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Services\Messaging\Matrix\MatrixEventSupport;

class IgnoredRoomEventHandler implements MatrixEventHandlerContract
{
    protected array $ignoredTypes = [
        'm.room.encrypted',
        'm.sticker',
        'io.element.functional_members',
        'm.room.join_rules',
        'm.room.history_visibility',
        'm.room.guest_access',
        'm.bridge',
        'uk.half-shot.bridge',
        'm.space.child',
        'm.space.parent',
        'com.beeper.chatwoot.conversation_id',
        'com.beeper.backfill_status',
        'com.beeper.rooms.note_to_self',
        'com.beeper.support_chat',
        'fi.mau.dummy.portal_created',
        'com.beeper.message_send_status',
        'com.beeper.feed',
        'org.matrix.msc2716.marker',
        'org.matrix.msc3401.call.member',
        'uk.half-shot.matrix-hookshot.feed',
        'm.room.plumbing',
        'm.room.related_groups',
        'com.beeper.room_features',
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
            'configured_room_ignore',
            self::class,
            ['room' => $context->roomId]
        );
    }
}
