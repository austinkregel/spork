<?php

declare(strict_types=1);

namespace App\Events\Models\Email;

use App\Events\AbstractLogicalEvent;
use App\Models\Email;

class EmailUpdated extends AbstractLogicalEvent
{
    public function __construct(
        public Email $model,
    ) {}
}
