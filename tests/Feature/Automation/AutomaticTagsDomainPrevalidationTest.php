<?php

declare(strict_types=1);

namespace Tests\Feature\Automation;

use App\Models\Condition;
use App\Models\Tag;
use App\Services\ConditionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\NullLogger;
use Tests\TestCase;

class AutomaticTagsDomainPrevalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_only_tag_is_not_evaluated_against_email_payload(): void
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
        ]);

        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'netflix',
        ]);

        $service = new ConditionService(new NullLogger);

        $this->assertFalse($service->process($tag, [
            'email' => [
                'subject' => 'Netflix receipt',
            ],
        ]));
    }

    public function test_in_condition_does_not_pass_when_actual_value_is_missing(): void
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create([
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
        ]);

        $tag->conditions()->create([
            'parameter' => 'transaction.personal_finance_category',
            'comparator' => Condition::COMPARATOR_IN,
            'value' => 'GENERAL_MERCHANDISE,CLOTHING_AND_ACCESSORIES',
        ]);

        $service = new ConditionService(new NullLogger);

        // No `transaction.*` in payload.
        $this->assertFalse($service->process($tag, [
            'article' => [
                'title' => 'Some RSS article',
            ],
        ]));
    }
}
