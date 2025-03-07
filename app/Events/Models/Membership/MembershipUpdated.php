<?php

declare(strict_types=1);

namespace App\Events\Models\Membership;

use App\Events\AbstractLogicalEvent;
use App\Models\Membership;

class MembershipUpdated extends AbstractLogicalEvent
{
    public function __construct(
        public Membership $model,
    ) {}
}
