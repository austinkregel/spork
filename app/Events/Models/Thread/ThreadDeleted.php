<?php

declare(strict_types=1);

namespace App\Events\Models\Thread;

use App\Events\AbstractLogicalEvent;
use App\Models\Thread;

class ThreadDeleted extends AbstractLogicalEvent
{
    public function __construct(
        public Thread $model,
    ) {
    }
}
