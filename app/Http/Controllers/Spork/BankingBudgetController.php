<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Finance\Budget;
use App\Services\Finance\BankingDashboardService;
use App\Services\Finance\BudgetCalculationService;
use App\Services\Finance\DefaultBudgetService;
use Inertia\Inertia;
use Inertia\Response;

class BankingBudgetController
{
    public function show(
        Budget $budget,
        BankingDashboardService $dashboardService,
        BudgetCalculationService $budgetCalculationService,
        DefaultBudgetService $defaultBudgetService,
    ): Response {
        $user = request()->user();
        abort_unless($user !== null, 404);
        abort_unless($budget->user_id === $user->id, 404);

        $budget->load('tags');
        $netMonthlyIncome = $defaultBudgetService->resolveNetMonthlyIncome($user);
        $monthlyBudgetAmount = $this->normalizeToMonthlyAmount((float) $budget->amount, $budget);
        $stats = $budgetCalculationService->getPeriodStats($budget, now('UTC'));
        $monthlySpend = $this->normalizeToMonthlyAmount((float) ($stats['total_spend'] ?? 0.0), $budget);
        $expectedPercentOfIncome = $netMonthlyIncome > 0 ? ($monthlyBudgetAmount / $netMonthlyIncome) * 100.0 : 0.0;
        $actualPercentOfIncome = $netMonthlyIncome > 0 ? ($monthlySpend / $netMonthlyIncome) * 100.0 : 0.0;

        return Inertia::render('Banking/BudgetShow', [
            'title' => $budget->name,
            'navigation' => $dashboardService->navigation('budgets'),
            'budget' => [
                'id' => $budget->id,
                'name' => $budget->name,
                'amount' => $budget->amount,
                'frequency' => $budget->getFrequencyEnum(),
                'interval' => $budget->getIntervalInt(),
                'started_at' => $budget->started_at?->toDateString(),
                'count' => $budget->count,
                'tags' => $budget->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->all(),
                'net_monthly_income' => $netMonthlyIncome,
                'expected_monthly_budget_amount' => $monthlyBudgetAmount,
                'actual_monthly_spend' => $monthlySpend,
                'expected_percent_of_income' => round($expectedPercentOfIncome, 2),
                'actual_percent_of_income' => round($actualPercentOfIncome, 2),
            ],
            'stats' => $stats,
            'transactions' => $budgetCalculationService->getPeriodTransactions($budget, now('UTC'), 200),
            'past_periods' => $budgetCalculationService->getPreviousPeriodGroups($budget, now('UTC'), 6, 75),
            'tags' => $dashboardService->tagOptions($user),
        ]);
    }

    protected function normalizeToMonthlyAmount(float $amount, Budget $budget): float
    {
        $interval = max(1, (int) $budget->getIntervalInt());
        $frequency = strtoupper((string) $budget->getFrequencyEnum());

        return match ($frequency) {
            'DAILY' => ($amount * (365 / 12)) / $interval,
            'WEEKLY' => ($amount * (52 / 12)) / $interval,
            'YEARLY', 'ANNUALLY' => ($amount / 12) / $interval,
            default => $amount / $interval,
        };
    }
}
