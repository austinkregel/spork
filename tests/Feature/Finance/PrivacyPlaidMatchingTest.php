<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Jobs\Finance\LinkPrivacyTransactionsToPlaidJob;
use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrivacyPlaidMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_exact_match(): void
    {
        $user = User::factory()->create();
        $plaidCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PLAID,
        ]);
        $privacyCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $plaidCredential->id,
        ]);

        $plaidTx = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => 40.01,
            'date' => now()->toDateString(),
        ]);

        $privacyTx = PrivacyTransaction::query()->create([
            'credential_id' => $privacyCredential->id,
            'privacy_transaction_id' => 'tx-privacy-1',
            'amount_cents' => 4001,
            'result' => 'APPROVED',
            'status' => 'SETTLED',
            'date_authorized' => now(),
            'data' => [],
        ]);

        (new LinkPrivacyTransactionsToPlaidJob($privacyCredential, ['days' => 7]))->handle(app(\App\Services\Finance\PrivacyPlaidMatcher::class));

        $this->assertDatabaseHas('privacy_transaction_matches', [
            'transaction_id' => $plaidTx->id,
            'privacy_transaction_id' => $privacyTx->id,
            'match_method' => 'exact_amount_date',
        ]);
    }

    public function test_links_batch_sum_match(): void
    {
        $user = User::factory()->create();
        $plaidCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PLAID,
        ]);
        $privacyCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $plaidCredential->id,
        ]);

        $plaidTx = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => 25.00,
            'date' => now()->toDateString(),
        ]);

        $p1 = PrivacyTransaction::query()->create([
            'credential_id' => $privacyCredential->id,
            'privacy_transaction_id' => 'tx-privacy-a',
            'amount_cents' => 1000,
            'result' => 'APPROVED',
            'status' => 'SETTLED',
            'date_authorized' => now(),
            'data' => [],
        ]);
        $p2 = PrivacyTransaction::query()->create([
            'credential_id' => $privacyCredential->id,
            'privacy_transaction_id' => 'tx-privacy-b',
            'amount_cents' => 1500,
            'result' => 'APPROVED',
            'status' => 'SETTLED',
            'date_authorized' => now(),
            'data' => [],
        ]);

        (new LinkPrivacyTransactionsToPlaidJob($privacyCredential, ['days' => 7]))->handle(app(\App\Services\Finance\PrivacyPlaidMatcher::class));

        $rows = DB::table('privacy_transaction_matches')
            ->where('transaction_id', $plaidTx->id)
            ->pluck('privacy_transaction_id')
            ->all();

        sort($rows);
        $expected = [$p1->id, $p2->id];
        sort($expected);

        $this->assertSame($expected, $rows);
    }
}
