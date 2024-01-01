<?php

declare(strict_types=1);

namespace App\Events\Models\Budget;

use App\Events\AbstractLogicalEvent;
use App\Models\Finance\Budget;

class BudgetCreating extends AbstractLogicalEvent
{
    public function __construct(
        public Budget $model,
    ) {
    }
}
