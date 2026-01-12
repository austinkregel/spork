<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Budget;
use App\Models\Person;
use App\Models\Tag;
use App\Models\User;

class DefaultBudgetService
{
    public const DEFAULT_GROSS_ANNUAL_INCOME = 75_000.0;

    /**
     * Reasonable effective tax rate used to estimate net income from gross income.
     */
    public const DEFAULT_TAX_RATE = 0.25;

    /**
     * These numbers are pretty arbitrary, and Admins should adjust as needed.
     *
     * @var array<string, float>
     */
    public const BUDGET_RATIOS = [
        'Housing (Rent/Mortgage)' => 0.299,
        'Utilities' => 0.043,
        'Transportation' => 0.075,
        'Food' => 0.107,
        'Insurance' => 0.032,
        'Subscriptions & Software' => 0.021,
        'Personal / Household' => 0.053,
        'Donations' => 0.015,
    ];

    /**
     * @var array<string, array<int, string>>
     */
    public const BUDGET_TAGS = [
        // We intentionally attach to existing automatic tags for the user.
        // Housing rolls up into "bills" by default (and avoids creating extra tags in code paths/tests).
        'Housing (Rent/Mortgage)' => ['bills'],
        'Utilities' => ['utilities'],
        'Transportation' => ['transportation'],
        'Food' => ['fast food/restaurants', 'doordash'],
        'Insurance' => ['insurance'],
        'Subscriptions & Software' => ['subscriptions', 'tech'],
        'Personal / Household' => ['personal/household'],
        'Donations' => ['donations'],
    ];

    /**
     * @return array<int, string>
     */
    public function standardBudgetNames(): array
    {
        return array_keys(self::BUDGET_RATIOS);
    }

    public function createForUser(User $user, bool $skipIfAnyBudgetExists = true): void
    {
        if ($skipIfAnyBudgetExists && $user->budgets()->exists()) {
            return;
        }

        $netMonthlyIncome = $this->resolveNetMonthlyIncome($user);

        foreach (self::BUDGET_RATIOS as $budgetName => $ratio) {
            $amount = round($netMonthlyIncome * $ratio, 2);
            $tagNames = self::BUDGET_TAGS[$budgetName] ?? [];

            /** @var Budget $budget */
            $budget = $user->budgets()->create([
                'name' => $budgetName,
                'amount' => $amount,
                'frequency' => Budget::FREQUENCY_MONTHLY,
                'interval' => '1',
                'started_at' => now('UTC')->startOfMonth(),
                'count' => null,
            ]);

            if ($tagNames === []) {
                continue;
            }

            $tagIds = array_map(
                fn (string $tagName): int => (int) Tag::findFromString($tagName, 'automatic')?->id,
                $tagNames,
            );

            $budget->tags()->syncWithoutDetaching(array_values(array_filter($tagIds)));
        }
    }

    public function resolveGrossAnnualIncome(User $user): float
    {
        /** @var string|int|float|null $income */
        $income = Person::query()->where('user_id', $user->id)->orderByDesc('id')->value('estimated_income');

        if ($income === null) {
            return self::DEFAULT_GROSS_ANNUAL_INCOME;
        }

        // Normalize common formats like "$75,000" or "75000/year".
        $normalized = preg_replace('/[^0-9.]/', '', (string) $income);
        $value = is_string($normalized) && $normalized !== '' ? (float) $normalized : null;

        return ($value !== null && $value > 0) ? $value : self::DEFAULT_GROSS_ANNUAL_INCOME;
    }

    public function resolveNetMonthlyIncome(User $user): float
    {
        $grossAnnualIncome = $this->resolveGrossAnnualIncome($user);
        $netAnnualIncome = $grossAnnualIncome * (1 - self::DEFAULT_TAX_RATE);

        return $netAnnualIncome / 12;
    }
}
