<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use App\Services\Finance\BudgetCalculationService;
use App\Services\Finance\BudgetPeriodHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_period_spend_for_tagged_transactions(): void
    {
        Carbon::setTestNow('2024-01-15 12:00:00');

        $user = User::factory()->create();

        /** @var Credential $credential */
        $credential = Credential::factory()->create(['user_id' => $user->id]);

        /** @var Account $account */
        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        /** @var Budget $budget */
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'amount' => 500,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'started_at' => Carbon::create(2024, 1, 1, 0, 0, 0, 'UTC'),
        ]);

        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'finance',
        ]);

        $budget->attachTag($tag);

        /** @var Transaction $transactionInPeriod */
        $transactionInPeriod = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Netflix Subscription',
            'amount' => -50.00,
            'date' => Carbon::create(2024, 1, 10, 0, 0, 0, 'UTC'),
            'pending' => false,
        ]);
        $transactionInPeriod->attachTag($tag);

        /** @var Transaction $pendingTransaction */
        $pendingTransaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Pending Netflix Subscription',
            'amount' => -99.00,
            'date' => Carbon::create(2024, 1, 11, 0, 0, 0, 'UTC'),
            'pending' => true,
        ]);
        $pendingTransaction->attachTag($tag);

        /** @var Transaction $transactionOutOfPeriod */
        $transactionOutOfPeriod = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Old Transaction',
            'amount' => -100.00,
            'date' => Carbon::create(2023, 12, 15, 0, 0, 0, 'UTC'),
            'pending' => false,
        ]);
        $transactionOutOfPeriod->attachTag($tag);

        $service = new BudgetCalculationService(new BudgetPeriodHelper);

        $stats = $service->getPeriodStats($budget, Carbon::now('UTC'));

        $this->assertSame(50.0, $stats['total_spend']);
        $this->assertSame(450.0, $stats['remaining']);
    }

    public function test_includes_privacy_transactions_in_period_spend(): void
    {
        Carbon::setTestNow('2024-01-15 12:00:00');

        $user = User::factory()->create();

        /** @var Credential $plaidCredential */
        $plaidCredential = Credential::factory()->create(['user_id' => $user->id]);

        /** @var Account $account */
        $account = Account::factory()->create([
            'credential_id' => $plaidCredential->id,
        ]);

        /** @var Credential $privacyCredential */
        $privacyCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        /** @var Budget $budget */
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'amount' => 500,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'started_at' => Carbon::create(2024, 1, 1, 0, 0, 0, 'UTC'),
        ]);

        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'finance',
        ]);

        $budget->attachTag($tag);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Plaid Transaction',
            'amount' => -50.00,
            'date' => Carbon::create(2024, 1, 10, 0, 0, 0, 'UTC'),
            'pending' => false,
        ]);
        $transaction->attachTag($tag);

        /** @var PrivacyTransaction $privacyTransaction */
        $privacyTransaction = PrivacyTransaction::factory()->create([
            'credential_id' => $privacyCredential->id,
            'amount_cents' => 1234, // $12.34
            'result' => 'APPROVED',
            'date_settled' => Carbon::create(2024, 1, 12, 0, 0, 0, 'UTC'),
            'date_authorized' => Carbon::create(2024, 1, 12, 0, 0, 0, 'UTC'),
        ]);
        $privacyTransaction->attachTag($tag);

        /** @var PrivacyTransaction $declinedPrivacyTransaction */
        $declinedPrivacyTransaction = PrivacyTransaction::factory()->create([
            'credential_id' => $privacyCredential->id,
            'amount_cents' => 9999, // should be ignored
            'result' => 'DECLINED',
            'date_settled' => Carbon::create(2024, 1, 13, 0, 0, 0, 'UTC'),
            'date_authorized' => Carbon::create(2024, 1, 13, 0, 0, 0, 'UTC'),
        ]);
        $declinedPrivacyTransaction->attachTag($tag);

        $service = new BudgetCalculationService(new BudgetPeriodHelper);

        $stats = $service->getPeriodStats($budget, Carbon::now('UTC'));

        $this->assertSame(62.34, $stats['total_spend']);
        $this->assertEqualsWithDelta(437.66, $stats['remaining'], 0.0001);
    }
}
