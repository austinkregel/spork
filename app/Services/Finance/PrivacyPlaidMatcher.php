<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PrivacyPlaidMatcher
{
    /**
     * @param  Collection<int, PrivacyTransaction>  $privacyTransactions
     * @param  Collection<int, Transaction>  $plaidTransactions
     * @return array<int, array{transaction_id: int, privacy_transaction_ids: array<int, int>, match_method: string, confidence: int}>
     */
    public function match(Collection $privacyTransactions, Collection $plaidTransactions): array
    {
        // Only match settled, approved Privacy items to Plaid ledger rows.
        $privacyTransactions = $privacyTransactions
            ->filter(fn (PrivacyTransaction $tx) => ($tx->result === 'APPROVED') && ($tx->status === 'SETTLED') && $tx->amount_cents !== null)
            ->values();

        // Plaid transactions are the ledger; match by absolute amount and date window.
        $plaidTransactions = $plaidTransactions
            ->filter(fn (Transaction $tx) => $tx->amount !== null && $tx->date !== null)
            ->values();

        $usedPrivacy = [];
        $matches = [];

        // First pass: exact 1:1 matches.
        foreach ($privacyTransactions as $privacy) {
            if (isset($usedPrivacy[$privacy->id])) {
                continue;
            }

            $candidate = $this->findExactMatch($privacy, $plaidTransactions);
            if (! $candidate) {
                continue;
            }

            $usedPrivacy[$privacy->id] = true;
            $matches[] = [
                'transaction_id' => $candidate->id,
                'privacy_transaction_ids' => [$privacy->id],
                'match_method' => 'exact_amount_date',
                'confidence' => 95,
            ];
        }

        // Second pass: batch-sum matches (multiple Privacy items sum to one Plaid transaction).
        foreach ($plaidTransactions as $plaid) {
            $targetCents = $this->toCents($plaid->amount);
            if ($targetCents === null) {
                continue;
            }

            // Skip if this Plaid transaction already has an exact match.
            $alreadyMatched = collect($matches)->contains(fn (array $m) => $m['transaction_id'] === $plaid->id);
            if ($alreadyMatched) {
                continue;
            }

            $candidates = $privacyTransactions
                ->filter(fn (PrivacyTransaction $p) => ! isset($usedPrivacy[$p->id]))
                ->filter(fn (PrivacyTransaction $p) => $this->withinDays($p->date_authorized, $plaid->date, 2))
                ->values();

            $subset = $this->findSubsetSum($candidates, $targetCents);
            if ($subset === []) {
                continue;
            }

            foreach ($subset as $privacyId) {
                $usedPrivacy[$privacyId] = true;
            }

            $matches[] = [
                'transaction_id' => $plaid->id,
                'privacy_transaction_ids' => $subset,
                'match_method' => 'batch_sum',
                'confidence' => 80,
            ];
        }

        return $matches;
    }

    protected function findExactMatch(PrivacyTransaction $privacy, Collection $plaidTransactions): ?Transaction
    {
        $privacyCents = (int) $privacy->amount_cents;

        /** @var Transaction|null $match */
        $match = $plaidTransactions->first(function (Transaction $plaid) use ($privacy, $privacyCents): bool {
            $plaidCents = $this->toCents($plaid->amount);
            if ($plaidCents === null) {
                return false;
            }

            return $plaidCents === $privacyCents
                && $this->withinDays($privacy->date_authorized, $plaid->date, 2);
        });

        return $match;
    }

    /**
     * Find a small subset of candidates whose cents sum equals target.
     *
     * @param  Collection<int, PrivacyTransaction>  $candidates
     * @return array<int, int> privacy_transaction_ids
     */
    protected function findSubsetSum(Collection $candidates, int $targetCents): array
    {
        // Guardrail: keep this fast. Sort by amount desc and cap search size.
        $candidates = $candidates
            ->filter(fn (PrivacyTransaction $p) => $p->amount_cents !== null)
            ->sortByDesc('amount_cents')
            ->take(20)
            ->values();

        $best = [];

        $search = function (int $index, int $sum, array $picked) use (&$search, $candidates, $targetCents, &$best): void {
            if ($sum === $targetCents) {
                $best = $picked;

                return;
            }

            if ($sum > $targetCents || $index >= $candidates->count() || $best !== []) {
                return;
            }

            /** @var PrivacyTransaction $candidate */
            $candidate = $candidates[$index];
            $amount = (int) $candidate->amount_cents;

            // Pick
            $search($index + 1, $sum + $amount, array_merge($picked, [$candidate->id]));
            // Skip
            $search($index + 1, $sum, $picked);
        };

        $search(0, 0, []);

        return $best;
    }

    protected function withinDays($privacyDate, $plaidDate, int $days): bool
    {
        if (! $privacyDate || ! $plaidDate) {
            return false;
        }

        $p = CarbonImmutable::parse($privacyDate)->startOfDay();
        $t = CarbonImmutable::parse($plaidDate)->startOfDay();

        return $p->diffInDays($t) <= $days;
    }

    protected function toCents(float $amount): ?int
    {
        $value = abs($amount);

        return (int) round($value * 100);
    }
}
