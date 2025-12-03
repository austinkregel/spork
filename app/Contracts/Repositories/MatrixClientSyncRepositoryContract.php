<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Credential;
use App\Models\User;

interface MatrixClientSyncRepositoryContract
{
    public function process(array $sync, Credential $credential, User $user): void;

    public function processRoom(string $roomId, array $room, Credential $credential, User $user): void;

    public function processEvent(array $event, Credential $credential, User $user): void;
}

