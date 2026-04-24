<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MatrixClientSyncRepositoryContract;
use App\Data\Matrix\MatrixEventContext;
use App\Data\Matrix\MatrixSyncState;
use App\Models\Credential;
use App\Models\Thread;
use App\Models\User;
use App\Services\Messaging\Matrix\MatrixEventHandlerRegistry;
use App\Services\Messaging\Matrix\MatrixEventSupport;
use Psr\Log\LoggerInterface;

class MatrixClientSyncRepository implements MatrixClientSyncRepositoryContract
{
    protected MatrixSyncState $state;

    public function __construct(
        protected LoggerInterface $logger,
        protected MatrixEventHandlerRegistry $registry,
        protected MatrixEventSupport $support,
    ) {
        $this->state = new MatrixSyncState;
    }

    public function process(array $sync, Credential $credential, User $user): void
    {
        $events = $sync['account_data']['events'] ?? [];

        foreach ($events as $event) {
            $this->processEvent($event, $credential, $user);
        }

        $rooms = json_decode(json_encode($sync['rooms']['join'] ?? []), true);

        foreach ($rooms as $id => $room) {
            $this->processRoom($id, $room, $credential, $user);
        }
    }

    public function processRoom(string $roomId, array $room, Credential $credential, User $user): void
    {
        $events = array_merge(
            $room['state']['events'] ?? [],
            $room['timeline']['events'] ?? []
        );

        foreach ($events as $event) {
            $this->dispatchEvent($event, $credential, $user, $roomId, $room);
        }

        $thread = Thread::query()->with('participants')->firstWhere('thread_id', $roomId);
        $this->support->renameThreadToOtherParticipant($thread, $user);
    }

    public function processEvent(array $event, Credential $credential, User $user): void
    {
        $this->dispatchEvent($event, $credential, $user);
    }

    protected function dispatchEvent(array $event, Credential $credential, User $user, ?string $roomId = null, ?array $room = null): void
    {
        if (empty($event['type'])) {
            return;
        }

        $context = new MatrixEventContext(
            event: $event,
            state: $this->state,
            credential: $credential,
            user: $user,
            roomId: $roomId,
            room: $room,
        );

        if ($this->registry->dispatch($context)) {
            return;
        }

        $this->logger->warning('Unhandled Matrix event type', [
            'type' => $event['type'],
            'room' => $roomId,
            'event' => $event,
        ]);
    }
}
