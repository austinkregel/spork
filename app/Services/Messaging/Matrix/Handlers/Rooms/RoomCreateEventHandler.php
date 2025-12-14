<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Thread;
use Carbon\Carbon;

class RoomCreateEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.create';
    }

    public function handle(MatrixEventContext $context): void
    {
        if (! $context->roomId) {
            return;
        }

        Thread::firstOrCreate(
            ['thread_id' => $context->roomId],
            [
                'name' => $context->roomId,
                'origin_server_ts' => Carbon::createFromFormat('U', (int) round(($context->event['origin_server_ts'] ?? 0) / 1000)),
            ]
        );
    }
}










