<?php

declare(strict_types=1);

namespace App\Events\Models\Script;

use App\Events\AbstractLogicalEvent;
use App\Models\Spork\Script;

class ScriptUpdating extends AbstractLogicalEvent
{
    public function __construct(
        public Script $model,
    ) {}
}
