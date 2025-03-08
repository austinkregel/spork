<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Models\Transaction\TransactionCreated;
use App\Events\Models\Budget\BudgetOverspent;
use App\Listeners\Finance\CheckBudgetOverspend;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use App\Models\Finance\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckBudgetOverspendTest extends TestCase
{
    use RefreshDatabase;

    public function testHandleTransactionCreatedEvent()
    {
        Event::fake([BudgetOverspent::class]);

        $transaction = Transaction::factory()->create();
        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspend();
        $listener->handle($event);

        Event::assertNotDispatched(BudgetOverspent::class);
    }

    public function testCheckIfTransactionMatchesAnyDefinedBudget()
    {
        Event::fake([BudgetOverspent::class]);

        $budget = Budget::factory()->create();
        $transaction = Transaction::factory()->create();
        $transaction->tags()->attach($budget->tags->pluck('id')->toArray());

        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspend();
        $listener->handle($event);

        Event::assertNotDispatched(BudgetOverspent::class);
    }

    public function testCalculateSpendAmountSinceStartOfDefinedPeriod()
    {
        Event::fake([BudgetOverspent::class]);

        $budget = Budget::factory()->create(['amount' => 100]);
        $transaction = Transaction::factory()->create(['amount' => 50]);
        $transaction->tags()->attach($budget->tags->pluck('id')->toArray());

        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspend();
        $listener->handle($event);

        Event::assertNotDispatched(BudgetOverspent::class);
    }

    public function testFireBudgetOverspentEventIfBudgetIsOverspent()
    {
        Event::fake([BudgetOverspent::class]);

        $user = User::factory()->create();
        $credential = Credential::factory()->create(['user_id' => $user->id]);
        $account = Account::factory()->create(['credential_id' => $credential->id]);
        $budget = Budget::factory()->create(['amount' => 100, 'user_id' => $user->id]);
        $transaction = Transaction::factory()->create(['amount' => 150, 'account_id' => $account->id]);
        $transaction->tags()->attach($budget->tags->pluck('id')->toArray());

        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspend();
        $listener->handle($event);

        Event::assertDispatched(BudgetOverspent::class);
    }
}
