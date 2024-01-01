<?php

declare(strict_types=1);

namespace App\Events\Models\Research;

use App\Events\AbstractLogicalEvent;
use App\Models\Research;

class ResearchDeleted extends AbstractLogicalEvent
{
    public function __construct(
        public Research $model,
    ) {
    }
}
