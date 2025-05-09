<?php

declare(strict_types=1);

namespace App\Events\Models\Condition;

use App\Events\AbstractLogicalEvent;
use App\Models\Condition;

class ConditionUpdating extends AbstractLogicalEvent
{
    public function __construct(
        public Condition $model,
    ) {}
}
