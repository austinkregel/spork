<?php

declare(strict_types=1);

namespace App\Events\Models\Account;

use App\Events\AbstractLogicalEvent;
use App\Models\Finance\Account;

class AccountDeleted extends AbstractLogicalEvent
{
    public function __construct(
        public Account $model,
    ) {}
}
