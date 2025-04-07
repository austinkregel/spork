<?php

declare(strict_types=1);

namespace App\Events\Models\Budget;

use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;

class BudgetOverspentEvent
{
    public Budget $budget;

    public Transaction $transaction;

    public function __construct(Budget $budget, Transaction $transaction)
    {
        $this->budget = $budget;
        $this->transaction = $transaction;
    }
}
