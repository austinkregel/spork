<?php

declare(strict_types=1);

namespace App\Events\Models\Domain;

use App\Events\AbstractLogicalEvent;
use App\Models\Domain;

class DomainUpdated extends AbstractLogicalEvent
{
    public function __construct(
        public Domain $model,
    ) {}
}
