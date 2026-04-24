<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Models\Credential;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Services\Finance\PrivacyPlaidMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class LinkPrivacyTransactionsToPlaidJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{days?: int|null}  $options
     */
    public function __construct(
        public Credential $privacyCredential,
        public array $options = [],
    ) {}

    public function handle(PrivacyPlaidMatcher $matcher): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $days = (int) ($this->options['days'] ?? 30);
        $days = max(1, min($days, 365));

        $since = CarbonImmutable::now('UTC')->subDays($days);

        $privacy = PrivacyTransaction::query()
            ->where('credential_id', $this->privacyCredential->id)
            ->where('result', 'APPROVED')
            ->where('status', 'SETTLED')
            ->whereNotNull('amount_cents')
            ->where('date_authorized', '>=', $since)
            ->get();

        if ($privacy->isEmpty()) {
            return;
        }

        // Plaid transactions for the same owner: any account tied to the credential owner.
        $accountIds = $this->privacyCredential->user?->accounts()->pluck('account_id')->all() ?? [];
        if ($accountIds === []) {
            return;
        }

        $plaid = Transaction::query()
            ->whereIn('account_id', $accountIds)
            ->where('date', '>=', $since->toDateString())
            ->whereNotNull('amount')
            ->get();

        if ($plaid->isEmpty()) {
            return;
        }

        $matches = $matcher->match($privacy, $plaid);

        if ($matches === []) {
            return;
        }

        $rows = [];
        $now = now();

        foreach ($matches as $match) {
            foreach ($match['privacy_transaction_ids'] as $privacyId) {
                $rows[] = [
                    'transaction_id' => $match['transaction_id'],
                    'privacy_transaction_id' => $privacyId,
                    'match_method' => $match['match_method'],
                    'confidence' => $match['confidence'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows === []) {
            return;
        }

        // Avoid duplicating matches; table enforces uniqueness on privacy_transaction_id.
        DB::table('privacy_transaction_matches')->upsert(
            $rows,
            ['privacy_transaction_id'],
            ['transaction_id', 'match_method', 'confidence', 'updated_at']
        );
    }
}
