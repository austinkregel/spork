<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\Models\Transaction\TransactionCreated;
use App\Listeners\Finance\CheckBudgetOverspendListener;
use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckBudgetOverspendListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_overspent_event_fires_when_period_spend_exceeds_amount(): void
    {
        Carbon::setTestNow('2024-01-15 12:00:00');

        Event::fake();

        $user = User::factory()->create();
        $credential = Credential::factory()->create(['user_id' => $user->id]);
        $account = Account::factory()->create(['credential_id' => $credential->id]);

        /** @var Budget $budget */
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'started_at' => Carbon::create(2024, 1, 1, 0, 0, 0, 'UTC'),
        ]);

        $tag = Tag::factory()->create(['type' => 'finance']);
        $budget->attachTag($tag);

        // Existing spend in the current period
        $existingTransaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Existing Spend',
            'amount' => -80.00,
            'date' => Carbon::create(2024, 1, 5, 0, 0, 0, 'UTC'),
        ]);
        $existingTransaction->attachTag($tag);

        // New transaction that should push the budget over
        $newTransaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'More Spend',
            'amount' => -30.00,
            'date' => Carbon::create(2024, 1, 10, 0, 0, 0, 'UTC'),
        ]);
        $newTransaction->attachTag($tag);

        $listener = app(CheckBudgetOverspendListener::class);

        $listener->handle(new TransactionCreated($newTransaction));

        Event::assertDispatched(\App\Events\Models\Budget\BudgetOverspentEvent::class);
    }
}
