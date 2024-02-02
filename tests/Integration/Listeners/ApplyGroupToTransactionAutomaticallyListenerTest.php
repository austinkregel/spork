<?php
declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Models\Condition;
use App\Events\Models\Transaction\TransactionCreated;
use App\Listeners\Finance\ApplyUserAutomatedTagsToTransaction;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ApplyGroupToTransactionAutomaticallyListenerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockLogger = mock(LoggerInterface::class);
    }
    
    public function testHandleSuccess(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create();
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $handler = new ApplyUserAutomatedTagsToTransaction($this->mockLogger);
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessNotApplyBecauseTransactionIsFilteredOut(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create();
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_NOT_EQUAL,
            'value' => $transaction->name,
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction($this->mockLogger);
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessNotApplyBecauseTransactionIsNotGettingDoubleTagged(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create();
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_NOT_EQUAL,
            'value' => $transaction->name,
        ]);

        $transaction->tags()->attach($tag->id);

        $handler = new ApplyUserAutomatedTagsToTransaction($this->mockLogger);
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessLastAttachTag(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create();
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_EQUALS,
            'value' => $transaction->name,
        ]);

        $transaction->tags()->attach($tag->id);

        $handler = new ApplyUserAutomatedTagsToTransaction($this->mockLogger);
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessAttachTagWithRelationInConditional(): void
    {
        $category = \App\Models\Finance\Category::factory()->create();
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'category_id' => $category->category_id,
        ]);
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'bill',
        ]);
        $tag->conditions()->create([
            'parameter' => 'category.name',
            'comparator' => Condition::COMPARATOR_EQUALS,
            'value' => $category->name,
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction($this->mockLogger);
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessAttachTagWhereAmountGreaterThan(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'name' => 'Netflix',
            'amount' => 9.99,
        ]);
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'debits/withdrawals',
        ]);
        $tag->conditions()->create([
            'parameter' => 'amount',
            'comparator' => Condition::COMPARATOR_GREATER_THAN,
            'value' => '0',
        ]);
        $tag->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'fee',
        ]);
        $tag->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'transfer',
        ]);

        /** @var Tag $tag */
        $tag2 = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'credits/income',
        ]);
        $tag2->conditions()->create([
            'parameter' => 'amount',
            'comparator' => Condition::COMPARATOR_LESS_THAN,
            'value' => '0',
        ]);
        $tag2->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'fee',
        ]);
        $tag2->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'transfer',
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction($this->mockLogger);
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }

    public function testHandleSuccessAttachTagWithAllConditionsPassSetToFalse(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'name' => 'Netflix',
            'amount' => 9.99,
        ]);
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'debits/withdrawals',
            'must_all_conditions_pass' => false,
        ]);
        $tag->conditions()->create([
            'parameter' => 'amount',
            'comparator' => Condition::COMPARATOR_GREATER_THAN,
            'value' => '0',
        ]);
        $tag->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'fee',
        ]);
        $tag->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'transfer',
        ]);

        /** @var Tag $tag */
        $tag2 = Tag::factory()->create([
            'type' => 'automatic',
            'name' => 'credits/income',
        ]);
        $tag2->conditions()->create([
            'parameter' => 'amount',
            'comparator' => Condition::COMPARATOR_LESS_THAN,
            'value' => '0',
        ]);
        $tag2->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'fee',
        ]);
        $tag2->conditions()->create([
            'parameter' => 'name',
            'comparator' => Condition::COMPARATOR_NOT_LIKE,
            'value' => 'transfer',
        ]);

        $handler = new ApplyUserAutomatedTagsToTransaction($this->mockLogger);
        $event = new TransactionCreated($transaction);
        $handler->handle($event);

        $tags = $transaction->tags()->get();
        $this->assertCount(1, $tags);
        $this->assertSame($tag->id, $tags->first()->id);
    }
}
