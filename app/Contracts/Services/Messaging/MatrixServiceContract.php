<?php

declare(strict_types=1);

namespace App\Contracts\Services\Messaging;

interface MatrixServiceContract
{
    public function fetchEvent(string $event_id): ?array;
}


