<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Condition;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Services\ConditionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\NullLogger;
use Tests\TestCase;

class AutomatedTagsTransactionCategoryMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_category_name_matches_any_vendor_category_label(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'data' => [
                'category' => ['Food and Drink', 'Restaurants', 'Fast Food'],
            ],
        ]);

        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
        ]);

        $tag->conditions()->create([
            'parameter' => 'transaction.category.name',
            'comparator' => Condition::COMPARATOR_EQUALS,
            'value' => 'Fast Food',
        ]);

        $tag->conditions()->create([
            'parameter' => 'transaction.category.name',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'restau',
        ]);

        $service = new ConditionService(new NullLogger);

        $this->assertTrue($service->process($tag, [
            'transaction' => $transaction,
        ]));
    }
}
