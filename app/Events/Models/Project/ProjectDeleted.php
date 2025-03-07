<?php

declare(strict_types=1);

namespace App\Events\Models\Project;

use App\Events\AbstractLogicalEvent;
use App\Models\Project;

class ProjectDeleted extends AbstractLogicalEvent
{
    public function __construct(
        public Project $model,
    ) {}
}
