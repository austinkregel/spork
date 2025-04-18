<?php

declare(strict_types=1);

namespace App\Events\Models\ShortCode;

use App\Events\AbstractLogicalEvent;
use App\Models\ShortCode;

class ShortCodeDeleted extends AbstractLogicalEvent
{
    public function __construct(
        public ShortCode $model,
    ) {}
}
