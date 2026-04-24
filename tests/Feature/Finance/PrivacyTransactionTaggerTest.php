<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Condition;
use App\Models\Credential;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Tag;
use App\Models\User;
use App\Services\Finance\PrivacyTransactionTagger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTransactionTaggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_automatic_tags_using_transaction_name_mapping(): void
    {
        $user = User::factory()->create();

        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        $privacyTx = PrivacyTransaction::factory()->create([
            'credential_id' => $credential->id,
            'privacy_transaction_id' => 'ptx_1',
            'memo' => 'Consumer Energy',
            'amount_cents' => 500,
        ]);

        $tagMatchesName = Tag::query()->create([
            'name' => ['en' => 'utilities'],
            'slug' => 'utilities',
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
        ]);
        $user->tags()->syncWithoutDetaching([$tagMatchesName->getKey()]);
        $tagMatchesName->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'energy',
        ]);

        /** @var PrivacyTransactionTagger $tagger */
        $tagger = app(PrivacyTransactionTagger::class);
        $tagger->applyAutomaticTags($user, $privacyTx);

        $privacyTx->refresh()->load('tags');
        $this->assertTrue($privacyTx->tags->contains('id', $tagMatchesName->id));
    }

    public function test_category_based_conditions_fail_when_category_is_unset(): void
    {
        $user = User::factory()->create();

        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        $privacyTx = PrivacyTransaction::factory()->create([
            'credential_id' => $credential->id,
            'privacy_transaction_id' => 'ptx_2',
            'memo' => 'Some merchant',
            'amount_cents' => 500,
        ]);

        $tagCategoryRule = Tag::query()->create([
            'name' => ['en' => 'category-required'],
            'slug' => 'category-required',
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
        ]);
        $user->tags()->syncWithoutDetaching([$tagCategoryRule->getKey()]);
        $tagCategoryRule->conditions()->create([
            'parameter' => 'transaction.category.name',
            'comparator' => Condition::COMPARATOR_EQUALS,
            'value' => 'Utilities',
        ]);

        /** @var PrivacyTransactionTagger $tagger */
        $tagger = app(PrivacyTransactionTagger::class);
        $tagger->applyAutomaticTags($user, $privacyTx);

        $privacyTx->refresh()->load('tags');
        $this->assertFalse($privacyTx->tags->contains('id', $tagCategoryRule->id));
    }
}
