<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use App\Services\Finance\BudgetCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTaggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_sync_transaction_tags(): void
    {
        $user = User::factory()->create();
        /** @var Credential $credential */
        $credential = Credential::factory()->create(['user_id' => $user->id]);
        /** @var Account $account */
        $account = Account::factory()->create([
            'credential_id' => $credential->id,
            'account_id' => 'acct-1',
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => -50.00,
            'date' => now('UTC')->toDateString(),
        ]);

        /** @var Tag $tag */
        $tag = Tag::factory()->create(['type' => 'finance']);
        $user->attachTag($tag);

        $this->actingAs($user)
            ->put('http://spork.localhost/-/banking/transactions/'.$transaction->id.'/tags', [
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => Transaction::class,
            'taggable_id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_tag_other_users_transaction(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $credential = Credential::factory()->create(['user_id' => $other->id]);
        $account = Account::factory()->create([
            'credential_id' => $credential->id,
            'account_id' => 'acct-other',
        ]);
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => -20,
            'date' => now('UTC')->toDateString(),
        ]);

        $tag = Tag::factory()->create(['type' => 'finance']);
        $user->attachTag($tag);

        $this->actingAs($user)
            ->put('http://spork.localhost/-/banking/transactions/'.$transaction->id.'/tags', [
                'tag_ids' => [$tag->id],
            ])
            ->assertNotFound();
    }

    public function test_budget_stats_reflect_transaction_after_tagging(): void
    {
        $user = User::factory()->create();
        /** @var Credential $credential */
        $credential = Credential::factory()->create(['user_id' => $user->id]);
        /** @var Account $account */
        $account = Account::factory()->create([
            'credential_id' => $credential->id,
            'account_id' => 'acct-budget',
        ]);

        /** @var Tag $tag */
        $tag = Tag::factory()->create(['type' => 'finance']);
        $user->attachTag($tag);

        /** @var Budget $budget */
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'amount' => 100,
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);
        $budget->tags()->sync([$tag->id]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => -50.00,
            'date' => now('UTC')->toDateString(),
        ]);

        /** @var BudgetCalculationService $service */
        $service = app(BudgetCalculationService::class);

        $statsBefore = $service->getPeriodStats($budget->load('tags', 'user'), now('UTC'));
        $this->assertEquals(0.0, $statsBefore['total_spend']);

        $this->actingAs($user)
            ->put('http://spork.localhost/-/banking/transactions/'.$transaction->id.'/tags', [
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect();

        $statsAfter = $service->getPeriodStats($budget->load('tags', 'user'), now('UTC'));
        $this->assertEquals(50.0, $statsAfter['total_spend']);
    }
}
