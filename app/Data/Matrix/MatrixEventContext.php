<?php

declare(strict_types=1);

namespace App\Data\Matrix;

use App\Models\Credential;
use App\Models\User;

final class MatrixEventContext
{
    public function __construct(
        public readonly array $event,
        public readonly MatrixSyncState $state,
        public readonly ?Credential $credential = null,
        public readonly ?User $user = null,
        public readonly ?string $roomId = null,
        public readonly ?array $room = null,
    ) {}
}


