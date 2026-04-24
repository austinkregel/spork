<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BudgetShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_show_page_loads_transactions_including_privacy(): void
    {
        $user = User::factory()->create();

        /** @var Tag $tag */
        $tag = Tag::factory()->create(['type' => 'automatic']);
        $user->attachTag($tag);

        /** @var Budget $budget */
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Food',
            'amount' => 500,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);
        $budget->tags()->sync([$tag->id]);

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

        /** @var Transaction $plaidTx */
        $plaidTx = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => -50.00,
            'date' => now('UTC')->toDateString(),
            'name' => 'PLAID FOOD',
            'pending' => false,
        ]);
        $plaidTx->attachTag($tag);

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
            'result' => 'APPROVED',
            'date_settled' => now('UTC'),
            'date_authorized' => now('UTC'),
        ]);
        $privacyTx->attachTag($tag);

        $this->actingAs($user)
            ->get('http://spork.localhost/-/finance/banking/budgets/'.$budget->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banking/BudgetShow')
                ->where('budget.id', $budget->id)
                ->has('transactions')
                ->where('transactions', function ($rows) use ($plaidTx, $privacyTx) {
                    $rows = collect($rows);
                    $hasPlaid = $rows->contains(fn ($r) => (int) ($r['id'] ?? 0) === $plaidTx->id);
                    $hasPrivacy = $rows->contains(function ($r) use ($privacyTx) {
                        return (string) ($r['id'] ?? '') === 'privacy-'.$privacyTx->id
                            && ($r['privacy_transactions'] ?? []) === [];
                    });

                    return $hasPlaid && $hasPrivacy;
                }));
    }

    public function test_budget_show_page_includes_past_period_groups(): void
    {
        $now = CarbonImmutable::parse('2024-02-15 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $user = User::factory()->create();

        /** @var Tag $tag */
        $tag = Tag::factory()->create(['type' => 'automatic']);
        $user->attachTag($tag);

        /** @var Budget $budget */
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Food',
            'amount' => 500,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'count' => null,
            'started_at' => $now->startOfMonth(),
        ]);
        $budget->tags()->sync([$tag->id]);

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

        // A transaction last month that should appear in past_periods[0].
        /** @var Transaction $lastMonth */
        $lastMonth = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'amount' => -25.00,
            'date' => $now->subMonth()->startOfMonth()->addDays(2)->toDateString(),
            'name' => 'PAST FOOD',
            'pending' => false,
        ]);
        $lastMonth->attachTag($tag);

        $this->actingAs($user)
            ->get('http://spork.localhost/-/finance/banking/budgets/'.$budget->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banking/BudgetShow')
                ->has('past_periods')
                ->where('past_periods.0.period_start', $now->subMonth()->startOfMonth()->toDateString())
                ->where('past_periods.0.period_end', $now->startOfMonth()->toDateString())
                ->where('past_periods.1.period_start', $now->subMonths(2)->startOfMonth()->toDateString())
                ->where('past_periods.1.period_end', $now->subMonth()->startOfMonth()->toDateString())
                ->where('past_periods.0.transactions', function ($rows) use ($lastMonth) {
                    return collect($rows)->contains(fn ($r) => (int) ($r['id'] ?? 0) === $lastMonth->id);
                }));
    }
}
