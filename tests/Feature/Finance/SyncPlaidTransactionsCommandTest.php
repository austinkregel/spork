<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Jobs\Finance\BackfillPlaidTransactionsJob;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SyncPlaidTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_sync_dispatches_backfill_batch(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        /** @var Credential $credential */
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PLAID,
            'settings' => ['cursor' => 'abc123'],
        ]);

        $this->artisan('finance:plaid:sync', [
            '--user' => (string) $user->id,
            '--full' => true,
            '--begin' => '2024-01-01',
            '--end' => '2024-01-02',
            '--window-days' => 3650,
        ])->assertExitCode(0);

        $credential->refresh();
        $this->assertSame('abc123', $credential->settings['cursor'] ?? null);

        Bus::assertBatched(function (PendingBatch $batch) {
            $jobClasses = collect($batch->jobs)->map(fn ($job) => get_class($job));

            return $batch->name === 'Backfill Plaid Transactions'
                && $jobClasses->contains(BackfillPlaidTransactionsJob::class)
                && count($batch->jobs) === 1;
        });
    }

    public function test_dry_run_does_not_reset_cursor_or_dispatch(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        /** @var Credential $credential */
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PLAID,
            'settings' => ['cursor' => 'abc123'],
        ]);

        $this->artisan('finance:plaid:sync', [
            '--user' => (string) $user->id,
            '--full' => true,
            '--dry-run' => true,
            '--begin' => '2024-01-01',
            '--end' => '2024-01-02',
            '--window-days' => 3650,
        ])->assertExitCode(0);

        $credential->refresh();
        $this->assertSame('abc123', $credential->settings['cursor'] ?? null);

        Bus::assertNothingBatched();
    }
}
