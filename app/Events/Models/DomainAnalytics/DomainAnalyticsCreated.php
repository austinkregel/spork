<?php

declare(strict_types=1);

namespace App\Events\Models\DomainAnalytics;

use App\Events\AbstractLogicalEvent;
use App\Models\DomainAnalytics;

class DomainAnalyticsCreated extends AbstractLogicalEvent
{
    public function __construct(
        public DomainAnalytics $model,
    ) {
    }
}
