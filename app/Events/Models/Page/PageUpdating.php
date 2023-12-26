<?php

declare(strict_types=1);

namespace App\Events\Models\Page;

use App\Events\AbstractLogicalEvent;
use App\Models\Page;

class PageUpdating extends AbstractLogicalEvent
{
    public function __construct(
        public Page $model,
    ) {
    }
}
