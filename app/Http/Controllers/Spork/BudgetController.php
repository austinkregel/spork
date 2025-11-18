<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Finance\Budget;
use App\Services\Finance\BudgetCalculationService;
use Inertia\Inertia;

class BudgetController
{
    public function show(Budget $budget, BudgetCalculationService $budgetCalculationService)
    {
        $budget->load('user', 'tags.transactions');

        $stats = $budgetCalculationService->getPeriodStats($budget, now('UTC'));

        return Inertia::render('Budget/Index', [
            'title' => 'Budget Management',
            'budget' => $budget,
            'stats' => $stats,
        ]);
    }
}
