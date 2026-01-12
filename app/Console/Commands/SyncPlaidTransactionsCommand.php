<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Finance\BackfillPlaidTransactionsJob;
use App\Models\Credential;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncPlaidTransactionsCommand extends Command
{
    protected $signature = 'finance:plaid:sync
        {--credential-id= : Credential id for the Plaid credential}
        {--user= : User id or email (required unless --all or --credential-id)}
        {--all : Run for all plaid finance credentials}
        {--begin= : Begin date (YYYY-MM-DD) inclusive}
        {--end= : End date (YYYY-MM-DD) exclusive}
        {--window-days=30 : Window size in days for backfill jobs}
        {--full : Full historical sync (defaults begin=10 years ago, end=tomorrow)}
        {--dry-run : Do not enqueue, just print what would be dispatched}';

    protected $description = 'Backfill Plaid transactions using /transactions/get over a date range. Use --full to backfill 10 years.';

    public function handle(): int
    {
        $credentialId = $this->option('credential-id');
        $userOption = $this->option('user');
        $all = (bool) $this->option('all');
        $full = (bool) $this->option('full');
        $dryRun = (bool) $this->option('dry-run');
        $begin = $this->option('begin');
        $end = $this->option('end');
        $windowDays = max(1, (int) $this->option('window-days'));

        if (! $all && (! is_string($credentialId) || $credentialId === '') && (! is_string($userOption) || $userOption === '')) {
            $this->error('You must pass --user=<id|email>, --credential-id=<id>, or --all.');

            return self::FAILURE;
        }

        $credentials = Credential::query()
            ->where('type', Credential::TYPE_FINANCE)
            ->where('service', Credential::PLAID)
            ->when(is_string($credentialId) && $credentialId !== '', fn ($q) => $q->where('id', (int) $credentialId))
            ->when(! $all && is_string($userOption) && $userOption !== '' && (! is_string($credentialId) || $credentialId === ''), function ($q) use ($userOption) {
                $user = $this->resolveUser($userOption);

                return $q->where('user_id', $user->id);
            })
            ->orderBy('id')
            ->get();

        if ($credentials->isEmpty()) {
            $this->warn('No finance plaid credentials found.');

            return self::SUCCESS;
        }

        if (! $full && (! is_string($begin) || $begin === '' || ! is_string($end) || $end === '')) {
            $this->error('You must pass --full or both --begin=YYYY-MM-DD and --end=YYYY-MM-DD.');

            return self::FAILURE;
        }

        if ($full) {
            if (! is_string($begin) || $begin === '') {
                $begin = CarbonImmutable::now('UTC')->subYears(10)->toDateString();
            }
            if (! is_string($end) || $end === '') {
                // Use "tomorrow" as exclusive end bound to include today's transactions.
                $end = CarbonImmutable::now('UTC')->addDay()->toDateString();
            }
        }

        $start = CarbonImmutable::parse((string) $begin, 'UTC')->startOfDay();
        $stopExclusive = CarbonImmutable::parse((string) $end, 'UTC')->startOfDay();

        if ($start->gte($stopExclusive)) {
            $this->error('--begin must be before --end.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $estimatedWindows = (int) ceil($start->diffInDays($stopExclusive) / $windowDays);
            $this->info(sprintf(
                'Would dispatch %d Plaid backfill job(s) (%d credential(s) × ~%d window(s)).',
                $credentials->count() * $estimatedWindows,
                $credentials->count(),
                $estimatedWindows
            ));
            $this->line(sprintf('Credential IDs: %s', $credentials->pluck('id')->join(', ')));
            $this->line(sprintf('Options: %s', json_encode([
                'begin' => $start->toDateString(),
                'end' => $stopExclusive->toDateString(),
                'window_days' => $windowDays,
                'full' => $full,
            ])));

            return self::SUCCESS;
        }

        $jobs = [];
        $windowStart = $start;
        while ($windowStart->lt($stopExclusive)) {
            $windowEnd = $windowStart->addDays($windowDays);
            if ($windowEnd->gt($stopExclusive)) {
                $windowEnd = $stopExclusive;
            }

            foreach ($credentials as $credential) {
                $jobs[] = new BackfillPlaidTransactionsJob(
                    $credential,
                    $windowStart->toDateString(),
                    $windowEnd->toDateString()
                );
            }

            $windowStart = $windowEnd;
        }

        Bus::batch($jobs)
            ->name('Backfill Plaid Transactions')
            ->allowFailures()
            ->dispatch();

        $this->info(sprintf('Dispatched %d Plaid backfill job(s).', count($jobs)));

        return self::SUCCESS;
    }

    protected function resolveUser(string $value): User
    {
        if (is_numeric($value)) {
            /** @var User|null $user */
            $user = User::query()->find((int) $value);
        } else {
            /** @var User|null $user */
            $user = User::query()->where('email', $value)->first();
        }

        if (! $user) {
            $this->error("User not found for --user={$value}");
            exit(self::FAILURE);
        }

        return $user;
    }
}
