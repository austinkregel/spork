<?php

declare(strict_types=1);

namespace App\Events\Models\DomainRecord;

use App\Events\AbstractLogicalEvent;
use App\Models\DomainRecord;

class DomainRecordDeleted extends AbstractLogicalEvent
{
    public function __construct(
        public DomainRecord $model,
    ) {}
}
