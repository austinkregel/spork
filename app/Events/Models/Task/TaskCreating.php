<?php

declare(strict_types=1);

namespace App\Events\Models\Task;

use App\Events\AbstractLogicalEvent;
use App\Models\Task;

class TaskCreating extends AbstractLogicalEvent
{
    public function __construct(
        public Task $model,
    ) {}
}
