<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Budget;
use Carbon\Carbon;

class BudgetPeriodHelper
{
    /**
     * Compute the current period [start, end) for the given budget and time (UTC).
     */
    public function getCurrentPeriod(Budget $budget, Carbon $now): array
    {
        $now = $now->copy()->utc();
        $start = $budget->started_at?->copy()->utc();

        if ($start === null) {
            // Fallback: if no start is defined, treat "now" as both start and end.
            return [$now, $now];
        }

        if ($now->lessThan($start)) {
            // If we're querying before the anchor, walk backwards until we find the period containing "now".
            $periodEnd = $start;
            $periodStart = $this->previousOccurrenceStart($budget, $periodEnd);

            while ($now->lessThan($periodStart)) {
                $periodEnd = $periodStart;
                $periodStart = $this->previousOccurrenceStart($budget, $periodEnd);
            }

            return [$periodStart, $periodEnd];
        }

        $periodStart = $start;

        // Step from the anchor date until we find the period that contains "now".
        // For normal budgets (monthly/weekly/etc.) the iteration count is small.
        while (true) {
            $nextStart = $this->nextOccurrenceStart($budget, $periodStart);

            if ($now->lessThan($nextStart)) {
                return [$periodStart, $nextStart];
            }

            $periodStart = $nextStart;
        }
    }

    /**
     * Compute the previous period [start, end) relative to the current one.
     */
    public function getPreviousPeriod(Budget $budget, Carbon $now): array
    {
        [$currentStart] = $this->getCurrentPeriod($budget, $now);
        $previousEnd = $currentStart;

        $previousStart = $this->previousOccurrenceStart($budget, $previousEnd);

        return [$previousStart, $previousEnd];
    }

    protected function nextOccurrenceStart(Budget $budget, Carbon $currentStart): Carbon
    {
        $frequency = $budget->getFrequencyEnum();
        $interval = $budget->getIntervalInt();

        return match ($frequency) {
            Budget::FREQUENCY_DAILY => $currentStart->copy()->addDays($interval),
            Budget::FREQUENCY_WEEKLY => $currentStart->copy()->addWeeks($interval),
            Budget::FREQUENCY_BIWEEKLY => $currentStart->copy()->addWeeks(2 * $interval),
            Budget::FREQUENCY_SEMIMONTHLY => $currentStart->copy()->addDays(15 * $interval),
            Budget::FREQUENCY_MONTHLY => $currentStart->copy()->addMonths($interval),
            Budget::FREQUENCY_BIMONTHLY => $currentStart->copy()->addMonths(2 * $interval),
            Budget::FREQUENCY_YEARLY => $currentStart->copy()->addYears($interval),
            default => $currentStart->copy()->addMonths($interval),
        };
    }

    protected function previousOccurrenceStart(Budget $budget, Carbon $currentStart): Carbon
    {
        $frequency = $budget->getFrequencyEnum();
        $interval = $budget->getIntervalInt();

        return match ($frequency) {
            Budget::FREQUENCY_DAILY => $currentStart->copy()->subDays($interval),
            Budget::FREQUENCY_WEEKLY => $currentStart->copy()->subWeeks($interval),
            Budget::FREQUENCY_BIWEEKLY => $currentStart->copy()->subWeeks(2 * $interval),
            Budget::FREQUENCY_SEMIMONTHLY => $currentStart->copy()->subDays(15 * $interval),
            Budget::FREQUENCY_MONTHLY => $currentStart->copy()->subMonths($interval),
            Budget::FREQUENCY_BIMONTHLY => $currentStart->copy()->subMonths(2 * $interval),
            Budget::FREQUENCY_YEARLY => $currentStart->copy()->subYears($interval),
            default => $currentStart->copy()->subMonths($interval),
        };
    }
}
