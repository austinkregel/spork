<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\Credential;
use App\Models\Finance\PrivacyCard;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrivacyTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_renders_with_payload(): void
    {
        $user = User::factory()->create();

        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'api_key' => 'test',
        ]);

        PrivacyCard::query()->create([
            'credential_id' => $credential->id,
            'card_token' => 'card-1',
            'memo' => 'Test Card',
            'data' => [],
        ]);

        PrivacyTransaction::query()->create([
            'credential_id' => $credential->id,
            'privacy_transaction_id' => 'tx-1',
            'amount_cents' => 1234,
            'result' => 'APPROVED',
            'status' => 'SETTLED',
            'descriptor' => 'TEST',
            'date_authorized' => now(),
            'card_uuid' => 'card-1',
            'data' => [],
        ]);

        $tx = PrivacyTransaction::query()
            ->where('credential_id', $credential->id)
            ->where('privacy_transaction_id', 'tx-1')
            ->firstOrFail();

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'privacy-ui-tag',
        ]);
        $tx->tags()->attach($tag);

        $this->actingAs($user)
            ->get('http://spork.localhost/-/banking/privacy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banking/Index')
                ->where('tab', 'privacy')
                ->has('privacyData.recent_cards', 1)
                ->has('privacyData.transactions.data', 1)
                ->has('privacyData.transactions.data.0.tags', 1)
                ->has('privacyData.summary.open_cards_count')
                ->has('navigation'));
    }

    public function test_privacy_page_includes_team_owned_privacy_credentials(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $member = User::factory()->create();

        $owner->currentTeam->users()->attach($member);
        $member->switchTeam($owner->currentTeam);

        $credential = Credential::factory()->create([
            'user_id' => $owner->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'api_key' => 'test',
        ]);

        PrivacyTransaction::query()->create([
            'credential_id' => $credential->id,
            'privacy_transaction_id' => 'tx-1',
            'amount_cents' => 1234,
            'result' => 'APPROVED',
            'status' => 'SETTLED',
            'descriptor' => 'TEST',
            'date_authorized' => now(),
            'data' => [],
        ]);

        $this->actingAs($member)
            ->get('http://spork.localhost/-/banking/privacy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banking/Index')
                ->where('tab', 'privacy')
                ->has('privacyData.transactions.data', 1));
    }

    public function test_privacy_page_filters_out_closed_cards(): void
    {
        $user = User::factory()->create();

        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'api_key' => 'test',
        ]);

        PrivacyCard::query()->create([
            'credential_id' => $credential->id,
            'card_token' => 'card-open',
            'state' => 'OPEN',
            'memo' => 'Open Card',
            'data' => [],
        ]);

        PrivacyCard::query()->create([
            'credential_id' => $credential->id,
            'card_token' => 'card-closed',
            'state' => 'CLOSED',
            'memo' => 'Closed Card',
            'data' => [],
        ]);

        $this->actingAs($user)
            ->get('http://spork.localhost/-/banking/privacy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banking/Index')
                ->where('tab', 'privacy')
                ->has('privacyData.recent_cards', 1));
    }
}
