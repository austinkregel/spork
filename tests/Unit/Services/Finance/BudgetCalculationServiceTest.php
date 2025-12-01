<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
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
        ]);
        $transactionInPeriod->attachTag($tag);

        /** @var Transaction $transactionOutOfPeriod */
        $transactionOutOfPeriod = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Old Transaction',
            'amount' => -100.00,
            'date' => Carbon::create(2023, 12, 15, 0, 0, 0, 'UTC'),
        ]);
        $transactionOutOfPeriod->attachTag($tag);

        $service = new BudgetCalculationService(new BudgetPeriodHelper());

        $stats = $service->getPeriodStats($budget, Carbon::now('UTC'));

        $this->assertSame(50.0, $stats['total_spend']);
        $this->assertSame(450.0, $stats['remaining']);
    }
}





