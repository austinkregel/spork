<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\BankingPreference;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;

class BankingDashboardService
{
    public function __construct(
        protected BudgetCalculationService $budgetCalculationService,
        protected BudgetPeriodHelper $budgetPeriodHelper,
        protected DefaultBudgetService $defaultBudgetService,
    ) {}

    public function preferencesFor(User $user): BankingPreference
    {
        return $user->bankingPreference()->firstOrCreate([], [
            'pinned_accounts' => [],
            'pinned_budgets' => [],
            'settings' => [],
        ]);
    }

    public function buildOverview(User $user, string $range): array
    {
        [$startDate, $endDate] = $this->resolveRange($range);
        $preferences = $this->preferencesFor($user);
        $accounts = $this->buildAccounts($user, $preferences);
        $budgets = $this->buildBudgets($user, $preferences);

        return [
            'range' => strtoupper($range),
            'accounts' => $accounts,
            'pinned_accounts' => $this->filterPinned($accounts),
            'budgets' => $budgets,
            'pinned_budgets' => $this->filterPinned($budgets),
            'graphs' => $this->buildGraphData($user, $startDate, $endDate),
            'stats' => $this->buildStats($startDate, $endDate),
            'transactions' => $this->recentTransactions($user),
            'preferences' => $preferences,
            'quick_links' => [
                ['label' => 'Link New Account', 'route' => route('finance.banking.accounts')],
                ['label' => 'Manage Budgets', 'route' => route('finance.banking.budgets')],
                ['label' => 'Transactions', 'route' => route('finance.banking.transactions')],
            ],
            'tags' => $this->tagOptions($user),
        ];
    }

    public function buildAccounts(User $user, ?BankingPreference $preferences = null): array
    {
        $preferences ??= $this->preferencesFor($user);
        $pinnedOrder = $preferences->pinned_accounts ?? [];
        $accounts = $user->accounts()
            ->with('credential')
            ->get()
            ->map(function (Account $account) use ($pinnedOrder) {
                $pinned = in_array($account->account_id, $pinnedOrder, true);

                return [
                    'id' => $account->id,
                    'account_id' => $account->account_id,
                    'name' => $account->name ?? $account->official_name,
                    'mask' => $account->mask,
                    'type' => $account->type,
                    'subtype' => $account->subtype,
                    'balance' => $account->balance ?? 0.0,
                    'available' => $account->available ?? 0.0,
                    'credential' => [
                        'name' => $account->credential?->name,
                    ],
                    'updated_at' => $account->updated_at?->toIso8601String(),
                    'pinned' => $pinned,
                ];
            })
            ->all();

        usort($accounts, function (array $a, array $b) use ($pinnedOrder) {
            $aIndex = array_search($a['account_id'], $pinnedOrder, true);
            $bIndex = array_search($b['account_id'], $pinnedOrder, true);

            if ($aIndex === false && $bIndex === false) {
                return strnatcmp($a['name'] ?? '', $b['name'] ?? '');
            }

            if ($aIndex === false) {
                return 1;
            }

            if ($bIndex === false) {
                return -1;
            }

            return $aIndex <=> $bIndex;
        });

        return $accounts;
    }

    public function buildBudgets(User $user, ?BankingPreference $preferences = null): array
    {
        $preferences ??= $this->preferencesFor($user);
        $pinnedOrder = $preferences->pinned_budgets ?? [];
        $now = now('UTC');
        $netMonthlyIncome = $this->defaultBudgetService->resolveNetMonthlyIncome($user);

        $budgets = $user->budgets()
            ->with('tags')
            ->get()
            ->map(function (Budget $budget) use ($pinnedOrder, $now, $netMonthlyIncome) {
                $current = $this->budgetCalculationService->getPeriodStats($budget, $now);
                $previous = $this->budgetCalculationService->getPreviousPeriodStats($budget, $now);
                $pinned = in_array($budget->id, $pinnedOrder, true);
                $monthlyBudgetAmount = $this->normalizeToMonthlyAmount((float) $budget->amount, $budget);
                $monthlySpend = $this->normalizeToMonthlyAmount((float) ($current['total_spend'] ?? 0.0), $budget);
                $expectedPercentOfIncome = $netMonthlyIncome > 0 ? ($monthlyBudgetAmount / $netMonthlyIncome) * 100.0 : 0.0;
                $actualPercentOfIncome = $netMonthlyIncome > 0 ? ($monthlySpend / $netMonthlyIncome) * 100.0 : 0.0;

                return [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'amount' => $budget->amount,
                    'frequency' => $budget->getFrequencyEnum(),
                    'interval' => $budget->getIntervalInt(),
                    'started_at' => $budget->started_at?->toDateString(),
                    'count' => $budget->count,
                    'tags' => $budget->tags->map(fn (Tag $tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ]),
                    'current' => $current,
                    'previous' => $previous,
                    'delta' => $current['total_spend'] - $previous['total_spend'],
                    'pinned' => $pinned,
                    'net_monthly_income' => $netMonthlyIncome,
                    'expected_monthly_budget_amount' => $monthlyBudgetAmount,
                    'actual_monthly_spend' => $monthlySpend,
                    'expected_percent_of_income' => round($expectedPercentOfIncome, 2),
                    'actual_percent_of_income' => round($actualPercentOfIncome, 2),
                ];
            })
            ->all();

        usort($budgets, function (array $a, array $b) use ($pinnedOrder) {
            $aIndex = array_search($a['id'], $pinnedOrder, true);
            $bIndex = array_search($b['id'], $pinnedOrder, true);

            if ($aIndex === false && $bIndex === false) {
                return strnatcmp($a['name'], $b['name']);
            }

            if ($aIndex === false) {
                return 1;
            }

            if ($bIndex === false) {
                return -1;
            }

            return $aIndex <=> $bIndex;
        });

        return $budgets;
    }

    protected function normalizeToMonthlyAmount(float $amount, Budget $budget): float
    {
        $interval = max(1, (int) $budget->getIntervalInt());
        $frequency = strtoupper((string) $budget->getFrequencyEnum());

        // Normalize budget amount to an approximate "per month" figure for percent-of-income reporting.
        return match ($frequency) {
            'DAILY' => ($amount * (365 / 12)) / $interval,
            'WEEKLY' => ($amount * (52 / 12)) / $interval,
            'YEARLY', 'ANNUALLY' => ($amount / 12) / $interval,
            default => $amount / $interval, // MONTHLY and unknowns fall back to "per interval month"
        };
    }

    public function recentTransactions(User $user, int $limit = 10): array
    {
        $accountIds = $user->accounts()->pluck('account_id')->toArray();

        if (count($accountIds) === 0) {
            return [];
        }

        return Transaction::query()
            ->whereIn('account_id', $accountIds)
            ->with('tags')
            ->orderByDesc('date')
            ->limit($limit)
            ->get()
            ->map(function (Transaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'name' => $transaction->name,
                    'amount' => $transaction->amount,
                    'date' => optional($transaction->date)->toDateString(),
                    'tags' => $transaction->tags->map(fn (Tag $tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ]),
                ];
            })
            ->toArray();
    }

    public function navigation(string $active): array
    {
        $items = [
            ['label' => 'Overview', 'tab' => 'overview', 'href' => route('finance.banking.overview')],
            ['label' => 'Accounts', 'tab' => 'accounts', 'href' => route('finance.banking.accounts')],
            ['label' => 'Budgets', 'tab' => 'budgets', 'href' => route('finance.banking.budgets')],
            ['label' => 'Transactions', 'tab' => 'transactions', 'href' => route('finance.banking.transactions')],
            ['label' => 'Privacy', 'tab' => 'privacy', 'href' => route('finance.banking.privacy')],
            ['label' => 'Settings', 'tab' => 'settings', 'href' => route('finance.banking.settings')],
        ];

        return array_map(function (array $item) use ($active) {
            $item['active'] = $item['tab'] === $active;

            return $item;
        }, $items);
    }

    protected function filterPinned(array $items): array
    {
        return array_values(array_filter($items, fn (array $item) => $item['pinned'] ?? false));
    }

    public function tagOptions(User $user): array
    {
        return $user->tags()
            ->select('id', 'name')
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])
            ->toArray();
    }

    protected function resolveRange(string $selectedRange): array
    {
        return match (strtolower($selectedRange)) {
            'mtd' => [now()->startOfMonth(), now()],
            'ytd' => [now()->startOfYear(), now()],
            'all' => [now()->subYears(10), now()],
            '7d' => [now()->subDays(7), now()],
            '30d' => [now()->subDays(30), now()],
            '60d' => [now()->subDays(60), now()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    protected function buildGraphData(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $labels = iterator_to_array($startDate->copy()->startOfDay()->range($endDate->copy()->startOfDay())->map(
            fn (Carbon $date) => $date->format('Y-m-d')
        ));

        $graphData = $user->tags()
            ->with([
                'transactions' => fn ($query) => $query
                    ->whereBetween('date', [$startDate, $endDate])
                    ->where('name', 'not like', '%transfer%')
                    ->select(['amount', 'date']),
            ])
            ->get()
            ->reduce(function (array $carry, Tag $tag) {
                if ($tag->transactions->isEmpty()) {
                    return $carry;
                }

                $data = [
                    'label' => $tag->name,
                    'data' => $tag->transactions->reduce(function (array $sum, Transaction $transaction) {
                        $key = optional($transaction->date)->format('Y-m-d');

                        if ($key === null) {
                            return $sum;
                        }

                        $sum[$key] = ($sum[$key] ?? 0) + $transaction->amount;

                        return $sum;
                    }, []),
                ];

                return array_merge($carry, [$data]);
            }, []);

        return [
            'labels' => $labels,
            'datasets' => $graphData,
        ];
    }

    protected function buildStats(Carbon $currentStart, Carbon $currentEnd): array
    {
        $lastMonth = $currentStart->copy();
        $beforeLastMonth = $lastMonth->copy()->subMonth();

        return [
            'total_income' => [
                'current' => $this->sumTags('credit/income', $lastMonth, $lastMonth->copy()->endOfMonth(), true),
                'previous' => $this->sumTags('credit/income', $beforeLastMonth, $beforeLastMonth->copy()->endOfMonth(), true),
            ],
            'total_expenses' => [
                'current' => $this->sumTags('debit/expense', $lastMonth, $lastMonth->copy()->endOfMonth()),
                'previous' => $this->sumTags('debit/expense', $beforeLastMonth, $beforeLastMonth->copy()->endOfMonth()),
            ],
            'other' => [
                'current' => $this->sumTags([
                    'Fast Food',
                    'Food and Drink',
                    'Food and Beverage',
                    'Supermarkets and Groceries',
                    'Restaurants',
                ], $lastMonth, $lastMonth->copy()->endOfMonth()),
                'previous' => $this->sumTags([
                    'Fast Food',
                    'Food and Drink',
                    'Food and Beverage',
                    'Supermarkets and Groceries',
                    'Restaurants',
                ], $beforeLastMonth, $beforeLastMonth->copy()->endOfMonth()),
            ],
        ];
    }

    protected function sumTags(string|array $tagNames, Carbon $start, Carbon $end, bool $absolute = false): float
    {
        $query = Tag::query();

        if (is_array($tagNames)) {
            $query->whereIn('name->en', $tagNames);
        } else {
            $query->where('name->en', $tagNames);
        }

        return $query
            ->with([
                'transactions' => fn ($transactionQuery) => $transactionQuery
                    ->whereBetween('date', [$start, $end])
                    ->select(['amount', 'id', 'date']),
            ])
            ->get()
            ->reduce(function (float $carry, Tag $tag) use ($absolute) {
                $sum = $tag->transactions->sum('amount');

                return $carry + ($absolute ? abs($sum) : $sum);
            }, 0.0);
    }
}
