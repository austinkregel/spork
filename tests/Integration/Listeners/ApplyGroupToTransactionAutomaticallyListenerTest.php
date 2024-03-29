<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Models\Transaction\TransactionCreated;
use App\Listeners\Finance\ApplyUserAutomatedTagsToTransaction;
use App\Models\Condition;
use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyGroupToTransactionAutomaticallyListenerTest extends TestCase
{
    use RefreshDatabase;

    public function testHandleSuccess(): void
    {
        $user = User::factory()->createQuietly();
        $credential = Credential::factory()->create([
            'name' => 'Bank Name',
            'type' => 'finance',
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
        ]);
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction();
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessNotApplyBecauseTransactionIsFilteredOut(): void
    {
        $user = User::factory()->createQuietly();
        $credential = Credential::factory()->create([
            'name' => 'Bank Name',
            'type' => 'finance',
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
        ]);
        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_NOT_EQUAL,
            'value' => $transaction->name,
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction();
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(0, $tags);
    }

    public function testHandleSuccessNotApplyBecauseTransactionIsNotGettingDoubleTagged(): void
    {
        $user = User::factory()->createQuietly();
        $credential = Credential::factory()->create([
            'name' => 'Bank Name',
            'type' => 'finance',
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
        ]);
        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_EQUALS,
            'value' => $transaction->name,
        ]);

        $transaction->tags()->attach($tag->id);
        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);

        $handler = new ApplyUserAutomatedTagsToTransaction();
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessLastAttachTag(): void
    {
        $user = User::factory()->createQuietly();
        $credential = Credential::factory()->create([
            'name' => 'Bank Name',
            'type' => 'finance',
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
        ]);
        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_EQUALS,
            'value' => $transaction->name,
        ]);

        $transaction->tags()->attach($tag->id);

        $handler = new ApplyUserAutomatedTagsToTransaction();
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessAttachTagWithRelationInConditional(): void
    {
        $user = User::factory()->createQuietly();
        $credential = Credential::factory()->create([
            'name' => 'Bank Name',
            'type' => 'finance',
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'name' => 'Business Account #3810',
            'credential_id' => $credential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
        ]);
        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'business',
        ]);
        $this->assertCount(1, $user->tags()->get());

        $tag->conditions()->create([
            'parameter' => 'account.name',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'Business',
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction();
        $event = new TransactionCreated($transaction);
        $handler->handle($event);
        $this->assertCount(1, $user->tags()->get());
        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessAttachTagWhereAmountGreaterThan(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'name' => 'Bank Name',
            'type' => 'finance',
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Netflix',
            'amount' => 9.99,
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction();
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(2, $tags);
        $this->assertSame('subscriptions', $tags->first()->name);
        $this->assertSame('debit/expense', $tags->last()->name);
    }

    public function testHandleSuccessAttachTagWithAllConditionsPassSetToFalse(): void
    {
        $user = User::factory()->createQuietly();
        $credential = Credential::factory()->create([
            'name' => 'Bank Name',
            'type' => 'finance',
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Netflix',
            'amount' => 9.99,
        ]);

        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'subscription',
            'must_all_conditions_pass' => false,
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.amount',
            'comparator' => Condition::COMPARATOR_GREATER_THAN,
            'value' => '0',
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'Netflix',
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction();
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }
}
