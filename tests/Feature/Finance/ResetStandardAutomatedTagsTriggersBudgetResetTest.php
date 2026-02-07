<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Budget;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetStandardAutomatedTagsTriggersBudgetResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_standard_tags_can_trigger_budget_reset_to_restore_budget_tag_links(): void
    {
        $user = User::factory()->createQuietly();

        // Verify user exists before running command
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        // Create a standard-ish budget that should be reset and re-created.
        $food = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Food',
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);

        $originalBudgetId = $food->id;
        $this->assertDatabaseHas('budgets', ['id' => $originalBudgetId, 'user_id' => $user->id]);

        // Run tag reset + trigger budgets reset.
        // Using expectsOutput to verify the command actually executed (not just exited)
        // and assertSuccessful() which provides clearer error messages than assertExitCode(0)
        $this->artisan('finance:reset-standard-automated-tags', [
            '--user' => (string) $user->id,
            '--no-rebuild' => true,
            '--reset-budgets' => true,
        ])
            ->expectsOutput(sprintf('User #%d <%s>', $user->id, $user->email ?? ''))
            ->expectsOutput('Done.')
            ->assertSuccessful();

        // Verify the original budget was deleted and recreated
        $this->assertDatabaseMissing('budgets', ['id' => $originalBudgetId]);

        // Food budget should exist (recreated) and be linked to a standard tag.
        $food = Budget::query()
            ->where('user_id', $user->id)
            ->where('name', 'Food')
            ->firstOrFail();

        $this->assertNotSame($originalBudgetId, $food->id, 'Expected a new budget to be created, not the old one');

        // More robust: find the tag via spatie helper (creates/reads by name).
        $tag = Tag::findFromString('fast food/restaurants', 'automatic');

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => Budget::class,
            'taggable_id' => $food->id,
        ]);
    }
}
