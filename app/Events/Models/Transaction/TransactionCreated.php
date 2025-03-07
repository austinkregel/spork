<?php

declare(strict_types=1);

namespace App\Events\Models\Transaction;

use App\Events\AbstractLogicalEvent;
use App\Models\Finance\Transaction;

class TransactionCreated extends AbstractLogicalEvent
{
    public function __construct(
        public Transaction $model,
    ) {}
}
