<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Contracts\Services\ConditionServiceContract;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;

class PrivacyTransactionTagger
{
    public function __construct(
        private readonly ConditionServiceContract $conditionService,
    ) {}

    public function applyAutomaticTags(User $user, PrivacyTransaction $transaction): void
    {
        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();

        $payload = $this->buildConditionPayload($transaction);

        /** @var Collection<int, Tag> $tagsToApply */
        $tagsToApply = $tags->filter(fn (Tag $tag) => $this->conditionService->process($tag, $payload));

        foreach ($tagsToApply as $tag) {
            if (! $transaction->tags()->where('id', $tag->id)->exists()) {
                $transaction->tags()->attach($tag);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buildConditionPayload(PrivacyTransaction $transaction): array
    {
        // Intentional: keep the same condition key (`transaction.name`) as Plaid transactions.
        // When Privacy lacks category data, category-based conditions should naturally fail.
        $name = $transaction->memo ?: ($transaction->descriptor ?: $transaction->privacy_transaction_id);
        $amount = $transaction->amount_cents !== null ? ((float) $transaction->amount_cents / 100) : null;

        return [
            // Match the existing Condition rules shape (used for Plaid Transactions).
            'transaction' => [
                'name' => $name,
                'amount' => $amount,
                // Privacy doesn't provide Plaid-like categories here; leave null so category-based rules simply won't match.
                'category' => [
                    'name' => null,
                ],
            ],
            'privacy_transaction' => $transaction,
        ];
    }
}
