<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetStandardAutomatedTagsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_resets_and_rebuilds_standard_tags_for_a_user(): void
    {
        $this->actingAsUser();

        $credential = Credential::factory()->create([
            'user_id' => $this->user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PLAID,
        ]);

        $privacyCredential = Credential::factory()->create([
            'user_id' => $this->user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        $transaction = Transaction::factory()->create([
            'account_id' => $account->account_id,
            'name' => 'Monthly fee',
        ]);

        $privacyTransaction = PrivacyTransaction::factory()->create([
            'credential_id' => $privacyCredential->id,
            'memo' => 'Monthly fee',
            'descriptor' => 'Monthly fee',
            'privacy_transaction_id' => 'ptx-fee',
            'amount_cents' => 500,
        ]);

        // Ensure there's some preexisting tag data to blow away.
        $existing = Tag::factory()->create([
            'name' => ['en' => 'fees'],
            'slug' => ['en' => 'fees'],
            'type' => 'automatic',
        ]);
        $this->user->tags()->syncWithoutDetaching([$existing->getKey()]);
        $transaction->tags()->syncWithoutDetaching([$existing->getKey()]);
        $privacyTransaction->tags()->syncWithoutDetaching([$existing->getKey()]);

        // Verify the old tag exists before reset
        $oldTagId = $existing->id;
        $this->assertDatabaseHas('tags', ['id' => $oldTagId]);
        $this->assertTrue($transaction->tags->contains('id', $oldTagId));
        $this->assertTrue($privacyTransaction->tags->contains('id', $oldTagId));

        // Verify command executes successfully and produces expected output
        // Using expectsOutput ensures the command actually ran, not just exited with code 0
        $this->artisan('finance:reset-standard-automated-tags', [
            '--user' => (string) $this->user->id,
        ])
            ->expectsOutput(sprintf('User #%d <%s>', $this->user->id, $this->user->email ?? ''))
            ->expectsOutput('Done.')
            ->assertSuccessful();

        // Verify the old tag was deleted
        $this->assertDatabaseMissing('tags', ['id' => $oldTagId]);

        $transaction->refresh()->load('tags');
        $privacyTransaction->refresh()->load('tags');

        // Verify new tags were created and applied
        $feesTag = Tag::query()
            ->where('type', 'automatic')
            ->where('name->en', 'fees')
            ->first();

        $this->assertNotNull($feesTag, 'Expected a new "fees" tag to be created');
        $this->assertNotSame($oldTagId, $feesTag->id, 'Expected a new tag, not the old one');

        $this->assertTrue(
            $transaction->tags->contains(fn ($tag) => ($tag->name['en'] ?? $tag->name) === 'fees'),
            'Expected rebuilt transaction tags to include the standard "fees" tag.'
        );

        $this->assertTrue(
            $privacyTransaction->tags->contains(fn ($tag) => ($tag->name['en'] ?? $tag->name) === 'fees'),
            'Expected rebuilt privacy transaction tags to include the standard "fees" tag.'
        );
    }

    public function test_command_requires_user_or_all(): void
    {
        $this->artisan('finance:reset-standard-automated-tags')->assertExitCode(1);
    }
}
