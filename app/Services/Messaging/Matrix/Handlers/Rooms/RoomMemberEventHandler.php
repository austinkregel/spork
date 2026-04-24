<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\Rooms;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;
use App\Models\Credential;
use App\Models\Person;
use App\Models\Thread;
use App\Services\Messaging\Matrix\MatrixEventSupport;
use Carbon\Carbon;

class RoomMemberEventHandler implements MatrixEventHandlerContract
{
    public function __construct(
        protected MatrixEventSupport $support,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'm.room.member';
    }

    public function handle(MatrixEventContext $context): void
    {
        if (! $context->roomId || ! $context->credential || ! $context->user) {
            return;
        }

        $people = Person::whereJsonContains('identifiers', $context->event['sender'])->get();

        if ($people->isEmpty()) {
            Person::create([
                'name' => $context->event['content']['displayname'] ?? $context->event['sender'],
                'identifiers' => [$context->event['sender']],
                'user_id' => 1,
            ]);

            $people = Person::whereJsonContains('identifiers', $context->event['sender'])->get();
        }

        /** @var Credential $credential */
        $credential = $context->credential;

        foreach ($people as $person) {
            /** @var Thread $thread */
            $thread = Thread::firstOrCreate(
                ['thread_id' => $context->roomId],
                [
                    'name' => $context->roomId,
                    'origin_server_ts' => Carbon::createFromFormat('U', (int) round(($context->event['origin_server_ts'] ?? 0) / 1000)),
                ]
            );

            if ($thread->participants()->firstWhere('person_id', $person->id)) {
                continue;
            }

            $joinedAt = Carbon::createFromFormat('U', (int) round(($context->event['origin_server_ts'] ?? 0) / 1000));
            $thread->participants()->attach($person->id, ['joined_at' => $joinedAt]);

            if ($joinedAt->isAfter($thread->origin_server_ts)) {
                $thread->update(['origin_server_ts' => $joinedAt]);
            }

            if (empty($person->photo_url) && ! empty($context->event['content']['avatar_url'])) {
                $photo = $this->support->downloadMedia($credential, $context->event['content']['avatar_url']);

                if ($photo) {
                    $person->photo_url = $photo;
                    $person->save();
                }
            }
        }
    }
}
