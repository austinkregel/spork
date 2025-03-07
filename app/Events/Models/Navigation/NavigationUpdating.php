<?php

declare(strict_types=1);

namespace App\Events\Models\Navigation;

use App\Events\AbstractLogicalEvent;
use App\Models\Navigation;

class NavigationUpdating extends AbstractLogicalEvent
{
    public function __construct(
        public Navigation $model,
    ) {}
}
