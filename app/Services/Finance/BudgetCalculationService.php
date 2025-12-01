<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use Carbon\Carbon;

class BudgetCalculationService
{
    public function __construct(
        protected BudgetPeriodHelper $periodHelper,
    ) {}

    /**
     * Get period-to-date stats for the given budget at the specified UTC time.
     *
     * @return array{
     *     period_start: \Carbon\Carbon,
     *     period_end: \Carbon\Carbon,
     *     total_spend: float,
     *     remaining: float,
     *     usage_percentage: float
     * }
     */
    public function getPeriodStats(Budget $budget, Carbon $now): array
    {
        [$periodStart, $periodEnd] = $this->periodHelper->getCurrentPeriod($budget, $now);

        $totalSpend = $this->calculatePeriodSpend($budget, $periodStart, $periodEnd);

        $amount = (float) $budget->amount;

        // Treat expenses as positive spend, regardless of sign convention.
        $totalSpendNormalized = $totalSpend < 0 ? abs($totalSpend) : $totalSpend;

        $remaining = $amount - $totalSpendNormalized;
        $usage = $amount > 0.0 ? ($totalSpendNormalized / $amount) * 100.0 : 0.0;

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_spend' => $totalSpendNormalized,
            'remaining' => $remaining,
            'usage_percentage' => $usage,
        ];
    }

    public function getPreviousPeriodStats(Budget $budget, Carbon $now): array
    {
        [$periodStart, $periodEnd] = $this->periodHelper->getPreviousPeriod($budget, $now);
        $totalSpend = $this->calculatePeriodSpend($budget, $periodStart, $periodEnd);

        $amount = (float) $budget->amount;
        $totalSpendNormalized = $totalSpend < 0 ? abs($totalSpend) : $totalSpend;

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_spend' => $totalSpendNormalized,
            'remaining' => $amount - $totalSpendNormalized,
            'usage_percentage' => $amount > 0.0 ? ($totalSpendNormalized / $amount) * 100.0 : 0.0,
        ];
    }

    public function getSpendBetween(Budget $budget, Carbon $periodStart, Carbon $periodEnd): float
    {
        $spend = $this->calculatePeriodSpend($budget, $periodStart, $periodEnd);

        return $spend < 0 ? abs($spend) : $spend;
    }

    protected function calculatePeriodSpend(Budget $budget, Carbon $periodStart, Carbon $periodEnd): float
    {
        $tagIds = $budget->tags->pluck('id')->all();

        if (count($tagIds) === 0) {
            return 0.0;
        }

        $user = $budget->user;

        if (! $user) {
            return 0.0;
        }

        $accountIds = $user->accounts()->pluck('account_id');

        if ($accountIds->isEmpty()) {
            return 0.0;
        }

        return (float) Transaction::query()
            ->whereIn('account_id', $accountIds)
            ->where('date', '>=', $periodStart)
            ->where('date', '<', $periodEnd)
            ->where('name', 'not like', '%transfer%')
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            })
            ->sum('amount');
    }
}


