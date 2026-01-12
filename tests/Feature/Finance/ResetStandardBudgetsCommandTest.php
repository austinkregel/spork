<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Budget;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetStandardBudgetsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resets_only_standard_budgets_and_preserves_non_standard_budgets(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        Person::factory()->create([
            'user_id' => $user->id,
            'estimated_income' => '75000',
        ]);

        // Standard budget (wrong amount) + non-standard budget.
        Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Food',
            'amount' => 1.23,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);
        Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Custom Budget',
            'amount' => 999,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);

        $this->artisan('finance:reset-standard-budgets', ['--user' => (string) $user->id])
            ->assertExitCode(0);

        // Custom budget preserved.
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'name' => 'My Custom Budget',
            'amount' => 999,
        ]);

        // Standard budgets recreated; Food should now be the computed amount from defaults.
        /** @var Budget $food */
        $food = Budget::query()->where('user_id', $user->id)->where('name', 'Food')->firstOrFail();
        $this->assertSame(501.56, (float) $food->amount);
    }

    public function test_dry_run_makes_no_changes(): void
    {
        $user = User::factory()->create();
        Person::factory()->create([
            'user_id' => $user->id,
            'estimated_income' => '75000',
        ]);

        Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Food',
            'amount' => 1.23,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);

        $this->artisan('finance:reset-standard-budgets', ['--user' => (string) $user->id, '--dry-run' => true])
            ->assertExitCode(0);

        // Food should remain unchanged.
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'name' => 'Food',
            'amount' => 1.23,
        ]);
    }
}
