<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Finance\Budget;
use Inertia\Inertia;

class BudgetController
{
    public function show(Budget $budget)
    {
        $budget->load('user', 'tags.transactions');

        return Inertia::render('Budget/Index', [
            'title' => 'Budget Management',
            'budget' => $budget,
        ]);
    }
}
