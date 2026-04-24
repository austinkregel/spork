<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Credential;
use App\Models\Finance\Budget;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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

        $plaidSum = (float) Transaction::query()
            ->whereIn('account_id', $accountIds)
            ->where('date', '>=', $periodStart)
            ->where('date', '<', $periodEnd)
            ->where(function ($q) {
                $q->where('pending', false)->orWhereNull('pending');
            })
            ->where('name', 'not like', '%transfer%')
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            })
            ->sum('amount');

        // Normalize each source independently so mixed sources can't cancel each other out.
        $plaidSpend = $plaidSum < 0 ? abs($plaidSum) : $plaidSum;

        $privacyCredentialIds = Credential::query()
            ->where('user_id', $user->id)
            ->where('type', Credential::TYPE_FINANCE)
            ->where('service', Credential::PRIVACY)
            ->pluck('id')
            ->all();

        $privacySpend = 0.0;

        if (! empty($privacyCredentialIds)) {
            $privacyCents = (int) PrivacyTransaction::query()
                ->whereIn('credential_id', $privacyCredentialIds)
                ->where('result', 'APPROVED')
                ->where(function ($q) use ($periodStart, $periodEnd) {
                    // Prefer settled date; fall back to authorized date when settled is null.
                    $q->where(function ($q) use ($periodStart, $periodEnd) {
                        $q->whereNotNull('date_settled')
                            ->where('date_settled', '>=', $periodStart)
                            ->where('date_settled', '<', $periodEnd);
                    })->orWhere(function ($q) use ($periodStart, $periodEnd) {
                        $q->whereNull('date_settled')
                            ->whereNotNull('date_authorized')
                            ->where('date_authorized', '>=', $periodStart)
                            ->where('date_authorized', '<', $periodEnd);
                    });
                })
                ->whereHas('tags', function ($query) use ($tagIds) {
                    $query->whereIn('tags.id', $tagIds);
                })
                ->sum('amount_cents');

            $privacySum = (float) $privacyCents / 100;
            $privacySpend = $privacySum < 0 ? abs($privacySum) : $privacySum;
        }

        return $plaidSpend + $privacySpend;
    }

    /**
     * Return a mixed list of Plaid transactions and standalone Privacy transactions for the current period.
     *
     * Shape is compatible with the existing `TransactionsTable` UI component.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPeriodTransactions(Budget $budget, Carbon $now, int $limit = 100): array
    {
        [$periodStart, $periodEnd] = $this->periodHelper->getCurrentPeriod($budget, $now);

        return $this->getTransactionsBetween($budget, $periodStart, $periodEnd, $limit);
    }

    /**
     * Return a mixed list of Plaid transactions and standalone Privacy transactions for the given period.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTransactionsBetween(Budget $budget, Carbon $periodStart, Carbon $periodEnd, int $limit = 100): array
    {
        $tagIds = $budget->tags->pluck('id')->all();
        if (count($tagIds) === 0) {
            return [];
        }

        $user = $budget->user;
        if (! $user) {
            return [];
        }

        $accountIds = $user->accounts()->pluck('account_id');
        if ($accountIds->isEmpty()) {
            return [];
        }

        /** @var Collection<int, Transaction> $plaid */
        $plaid = Transaction::query()
            ->whereIn('account_id', $accountIds)
            ->where('date', '>=', $periodStart)
            ->where('date', '<', $periodEnd)
            ->where(function ($q) {
                $q->where('pending', false)->orWhereNull('pending');
            })
            ->where('name', 'not like', '%transfer%')
            ->with(['account', 'tags', 'privacyTransactions.tags'])
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            })
            ->orderByDesc('date')
            ->limit($limit)
            ->get();

        $linkedPrivacyIds = $plaid
            ->flatMap(fn (Transaction $t) => $t->privacyTransactions->pluck('id'))
            ->unique()
            ->values()
            ->all();

        $privacyCredentialIds = Credential::query()
            ->where('user_id', $user->id)
            ->where('type', Credential::TYPE_FINANCE)
            ->where('service', Credential::PRIVACY)
            ->pluck('id')
            ->all();

        $privacy = collect();
        if (! empty($privacyCredentialIds)) {
            /** @var Collection<int, PrivacyTransaction> $privacy */
            $privacy = PrivacyTransaction::query()
                ->whereIn('credential_id', $privacyCredentialIds)
                ->when($linkedPrivacyIds !== [], fn ($q) => $q->whereNotIn('id', $linkedPrivacyIds))
                ->where('result', 'APPROVED')
                ->where(function ($q) use ($periodStart, $periodEnd) {
                    $q->where(function ($q) use ($periodStart, $periodEnd) {
                        $q->whereNotNull('date_settled')
                            ->where('date_settled', '>=', $periodStart)
                            ->where('date_settled', '<', $periodEnd);
                    })->orWhere(function ($q) use ($periodStart, $periodEnd) {
                        $q->whereNull('date_settled')
                            ->whereNotNull('date_authorized')
                            ->where('date_authorized', '>=', $periodStart)
                            ->where('date_authorized', '<', $periodEnd);
                    });
                })
                ->with(['tags'])
                ->whereHas('tags', function ($query) use ($tagIds) {
                    $query->whereIn('tags.id', $tagIds);
                })
                ->orderByDesc('date_settled')
                ->orderByDesc('date_authorized')
                ->limit($limit)
                ->get();
        }

        $rows = [];

        foreach ($plaid as $t) {
            $rows[] = [
                'id' => $t->id,
                'name' => $t->name,
                'amount' => $t->amount,
                'date' => $t->date,
                'pending' => $t->pending,
                'account' => $t->account ? ['name' => $t->account->name] : null,
                'tags' => $t->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->all(),
                'privacy_transactions' => $t->privacyTransactions->map(function (PrivacyTransaction $p) {
                    return [
                        'id' => $p->id,
                        'amount_cents' => $p->amount_cents,
                        'status' => $p->status,
                        'result' => $p->result,
                        'descriptor' => $p->descriptor,
                        'memo' => $p->memo,
                        'mcc' => $p->mcc,
                        'tags' => $p->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->all(),
                        'pivot' => $p->pivot ? $p->pivot->toArray() : null,
                    ];
                })->all(),
            ];
        }

        foreach ($privacy as $p) {
            $date = $p->date_settled ?? $p->date_authorized;
            $amount = $p->amount_cents !== null ? -((float) $p->amount_cents / 100) : 0.0;
            $name = $p->memo ?: ($p->descriptor ?: $p->privacy_transaction_id);

            $rows[] = [
                'id' => 'privacy-'.$p->id,
                'name' => $name,
                'amount' => $amount,
                'date' => $date,
                'pending' => false,
                'account' => ['name' => 'Privacy'],
                'tags' => $p->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->all(),
                // Standalone Privacy transactions should render as a single row (not a row + an extra detail row).
                // The main transactions page only uses detail rows for Privacy transactions linked to Plaid transactions.
                'privacy_transactions' => [],
            ];
        }

        usort($rows, function (array $a, array $b) {
            $aDate = (string) ($a['date'] ?? '');
            $bDate = (string) ($b['date'] ?? '');

            return strcmp($bDate, $aDate);
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * Return grouped transactions for previous periods, newest-first.
     *
     * @return array<int, array{
     *     period_start: string,
     *     period_end: string,
     *     stats: array{period_start:\Carbon\Carbon,period_end:\Carbon\Carbon,total_spend:float,remaining:float,usage_percentage:float},
     *     transactions: array<int, array<string, mixed>>
     * }>
     */
    public function getPreviousPeriodGroups(Budget $budget, Carbon $now, int $periods = 6, int $perPeriodLimit = 50): array
    {
        $periods = max(0, $periods);
        if ($periods === 0) {
            return [];
        }

        $groups = [];
        $cursor = $now->copy()->utc();

        for ($i = 0; $i < $periods; $i++) {
            [$start, $end] = $this->periodHelper->getPreviousPeriod($budget, $cursor);

            $groups[] = [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'stats' => $this->getSpendBetweenStats($budget, $start, $end),
                'transactions' => $this->getTransactionsBetween($budget, $start, $end, $perPeriodLimit),
            ];

            // Step cursor to the start of the period we just processed so "previous" keeps marching back.
            $cursor = $start->copy()->utc();
        }

        return $groups;
    }

    /**
     * @return array{period_start:\Carbon\Carbon,period_end:\Carbon\Carbon,total_spend:float,remaining:float,usage_percentage:float}
     */
    protected function getSpendBetweenStats(Budget $budget, Carbon $periodStart, Carbon $periodEnd): array
    {
        $totalSpend = $this->calculatePeriodSpend($budget, $periodStart, $periodEnd);
        $amount = (float) $budget->amount;
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
}
