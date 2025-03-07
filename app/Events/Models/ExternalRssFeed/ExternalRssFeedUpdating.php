<?php

declare(strict_types=1);

namespace App\Events\Models\ExternalRssFeed;

use App\Events\AbstractLogicalEvent;
use App\Models\ExternalRssFeed;

class ExternalRssFeedUpdating extends AbstractLogicalEvent
{
    public function __construct(
        public ExternalRssFeed $model,
    ) {}
}
