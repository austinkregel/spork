<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Thread;
use App\Services\Messaging\Matrix\MatrixEventSupport;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class RoomNameEventHandler implements MatrixEventHandlerContract
{
    public function __construct(
        protected MatrixEventSupport $support,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.name';
    }

    public function handle(MatrixEventContext $context): void
    {
        if (! $context->roomId) {
            return;
        }

        $thread = Thread::firstWhere('thread_id', $context->roomId);

        if (! $thread) {
            Thread::create([
                'thread_id' => $context->roomId,
                'name' => $this->resolveRoomName($context),
                'origin_server_ts' => Carbon::now(),
            ]);

            return;
        }

        if (is_array($thread->name) && count($thread->name) === 1) {
            $thread->name = Arr::first($thread->name);
        }

        $name = $this->resolveRoomName($context);

        if (! $this->threadHasCustomName($thread)) {
            if ($name) {
                $thread->update(['name' => $name]);

                return;
            }

            $this->support->renameThreadToOtherParticipant($thread, $context->user);

            return;
        }

        // Thread already has a custom name, so never override it with DM naming logic.
    }

    protected function resolveRoomName(MatrixEventContext $context): ?string
    {
        $name = $context->event['content']['name'] ?? null;

        if (is_array($name)) {
            return Arr::first($name);
        }

        return $name;
    }

    protected function threadHasCustomName(Thread $thread): bool
    {
        $name = $thread->name;

        if (is_array($name)) {
            $name = Arr::first($name);
        }

        if (! is_string($name) || blank($name)) {
            return false;
        }

        return ! str_starts_with($name, '!');
    }
}
