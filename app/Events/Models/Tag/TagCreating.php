<?php

declare(strict_types=1);

namespace App\Events\Models\Tag;

use App\Events\AbstractLogicalEvent;
use App\Models\Tag;

class TagCreating extends AbstractLogicalEvent
{
    public function __construct(
        public Tag $model,
    ) {}
}
