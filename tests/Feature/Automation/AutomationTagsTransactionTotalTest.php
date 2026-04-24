<?php

declare(strict_types=1);

namespace Tests\Feature\Automation;

use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AutomationTagsTransactionTotalTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_index_transaction_total_includes_plaid_and_privacy(): void
    {
        $user = User::factory()->create();

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'combined-total',
        ]);

        // Plaid side: credential + account + transaction
        /** @var Credential $plaidCredential */
        $plaidCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PLAID,
        ]);

        /** @var Account $account */
        $account = Account::factory()->create([
            'credential_id' => $plaidCredential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => -50.00,
            'name' => 'PLAID TEST',
            'date' => now('UTC')->toDateString(),
        ]);
        $transaction->attachTag($tag);

        // Privacy side: privacy credential + privacy transaction
        /** @var Credential $privacyCredential */
        $privacyCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        /** @var PrivacyTransaction $privacyTx */
        $privacyTx = PrivacyTransaction::factory()->create([
            'credential_id' => $privacyCredential->id,
            'amount_cents' => 1234, // $12.34
            'date_settled' => now('UTC'),
            'date_authorized' => now('UTC'),
        ]);
        $privacyTx->attachTag($tag);

        $this->actingAs($user)
            ->get('http://spork.localhost/-/automations/tags')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Automation/Tags')
                ->has('tags.data')
                ->where('tags.data', fn ($tags) => collect($tags)->contains(function ($row) use ($tag) {
                    $id = (int) ($row['id'] ?? 0);
                    $total = (float) ($row['transactions_sum_amount'] ?? 0);

                    return $id === $tag->id && abs($total - (-37.66)) < 0.0001;
                })));
    }

    public function test_tag_show_transaction_total_includes_plaid_and_privacy(): void
    {
        $user = User::factory()->create();

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'combined-total',
        ]);

        /** @var Credential $plaidCredential */
        $plaidCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PLAID,
        ]);

        /** @var Account $account */
        $account = Account::factory()->create([
            'credential_id' => $plaidCredential->id,
        ]);

        /** @var Transaction $transaction */
        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => -50.00,
            'name' => 'PLAID TEST',
            'date' => now('UTC')->toDateString(),
        ]);
        $transaction->attachTag($tag);

        /** @var Credential $privacyCredential */
        $privacyCredential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        /** @var PrivacyTransaction $privacyTx */
        $privacyTx = PrivacyTransaction::factory()->create([
            'credential_id' => $privacyCredential->id,
            'amount_cents' => 1234,
            'date_settled' => now('UTC'),
            'date_authorized' => now('UTC'),
        ]);
        $privacyTx->attachTag($tag);

        $this->actingAs($user)
            ->get('http://spork.localhost/-/automations/tags/'.$tag->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Automation/TagShow')
                ->where('tag.id', $tag->id)
                ->where('tag.transactions_sum_amount', fn ($value) => abs(((float) $value) - (-37.66)) < 0.0001));
    }
}
