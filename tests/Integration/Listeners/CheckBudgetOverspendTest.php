<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Models\Budget\BudgetOverspentEvent;
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

class CheckBudgetOverspendTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_transaction_created_event()
    {
        Event::fake([BudgetOverspentEvent::class]);

        $transaction = Transaction::factory()->create();
        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspendListener;
        $listener->handle($event);

        Event::assertNotDispatched(BudgetOverspentEvent::class);
    }

    public function test_check_if_transaction_matches_any_defined_budget()
    {
        Event::fake([BudgetOverspentEvent::class]);

        $budget = Budget::factory()->create();
        $transaction = Transaction::factory()->create();
        $transaction->tags()->attach($budget->tags->pluck('id')->toArray());

        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspendListener;
        $listener->handle($event);

        Event::assertNotDispatched(BudgetOverspentEvent::class);
    }

    public function test_calculate_spend_amount_since_start_of_defined_period()
    {
        Event::fake([BudgetOverspentEvent::class]);

        $budget = Budget::factory()->create(['amount' => 100]);
        $transaction = Transaction::factory()->create(['amount' => 50]);
        $transaction->tags()->attach($budget->tags->pluck('id')->toArray());

        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspendListener;
        $listener->handle($event);

        Event::assertNotDispatched(BudgetOverspentEvent::class);
    }

    public function test_fire_budget_overspent_event_if_budget_is_overspent()
    {
        Event::fake([BudgetOverspentEvent::class]);
        Carbon::setTestNow(
            Carbon::parse('2025-01-20')
        );

        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => [
                'en' => 'Food',
            ],
        ]);
        $credential = Credential::factory()->create(['user_id' => $user->id]);
        $account = Account::factory()->create(['credential_id' => $credential->id]);
        $budget = Budget::factory()->create([
            'amount' => 100,
            'user_id' => $user->id,
            'frequency' => 'monthly',
            'started_at' => now()->subYears(5)->addMonths(3),
            'interval' => 1,
        ]);
        $transaction = Transaction::factory()->create([
            'date' => '2025-01-19',
            'amount' => 150,
            'account_id' => $account->account_id,
        ]);
        $transaction->tags()->sync($tag->id);
        $budget->tags()->sync($tag->id);

        $event = new TransactionCreated($transaction);

        $listener = new CheckBudgetOverspendListener;
        $listener->handle($event);

        Event::assertDispatched(BudgetOverspentEvent::class);
    }
}
