<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Finance\SyncPrivacyTransactionsJob;
use App\Models\Credential;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncPrivacyTransactionsCommand extends Command
{
    protected $signature = 'finance:privacy:sync
        {--credential-id= : Credential id for the Privacy credential (defaults to all privacy finance credentials)}
        {--begin= : Begin date (YYYY-MM-DD) inclusive}
        {--end= : End date (YYYY-MM-DD) exclusive (Privacy API semantics)}
        {--page-size=50 : Page size (1-1000)}
        {--full : Full historical sync (requires --begin, defaults end=tomorrow)}
        {--dry-run : Do not enqueue, just print what would be dispatched}';

    protected $description = 'Sync Privacy transactions via the Privacy v1 API using stored API-key credentials.';

    public function handle(): int
    {
        $credentialId = $this->option('credential-id');
        $begin = $this->option('begin');
        $end = $this->option('end');
        $pageSize = (int) $this->option('page-size');
        $full = (bool) $this->option('full');
        $dryRun = (bool) $this->option('dry-run');

        $credentials = Credential::query()
            ->where('type', Credential::TYPE_FINANCE)
            ->where('service', 'privacy')
            ->when($credentialId, fn ($q) => $q->where('id', $credentialId))
            ->get();

        if ($credentials->isEmpty()) {
            $this->warn('No finance privacy credentials found.');

            return self::SUCCESS;
        }

        if ($full && (! is_string($begin) || $begin === '')) {
            $this->error('--full requires --begin=YYYY-MM-DD');

            return self::FAILURE;
        }

        if ($full && (! is_string($end) || $end === '')) {
            $end = CarbonImmutable::now('UTC')->addDay()->toDateString();
        }

        $options = [
            'begin' => is_string($begin) && $begin !== '' ? $begin : null,
            'end' => is_string($end) && $end !== '' ? $end : null,
            'page_size' => max(1, min($pageSize, 1000)),
            // \"all\" in practice means APPROVED + DECLINED based on v1 docs.
            'results' => ['APPROVED', 'DECLINED'],
        ];

        if (! empty($options['begin']) && ! empty($options['end']) && $full) {
            $jobs = [];
            $start = CarbonImmutable::parse((string) $options['begin'], 'UTC')->startOfDay();
            $stopExclusive = CarbonImmutable::parse((string) $options['end'], 'UTC')->startOfDay();

            $windowStart = $start;
            while ($windowStart->lt($stopExclusive)) {
                $windowEnd = $windowStart->addDays(30);
                if ($windowEnd->gt($stopExclusive)) {
                    $windowEnd = $stopExclusive;
                }

                $rangeOptions = array_merge($options, [
                    'begin' => $windowStart->toDateString(),
                    'end' => $windowEnd->toDateString(),
                ]);

                foreach ($credentials as $credential) {
                    $jobs[] = new SyncPrivacyTransactionsJob($credential, $rangeOptions);
                }

                $windowStart = $windowEnd;
            }
        } else {
            $jobs = $credentials->map(fn (Credential $credential) => new SyncPrivacyTransactionsJob($credential, $options))->all();
        }

        if ($dryRun) {
            $this->info(sprintf('Would dispatch %d SyncPrivacyTransactionsJob job(s).', count($jobs)));
            $this->line(sprintf('Options: %s', json_encode($options)));

            return self::SUCCESS;
        }

        Bus::batch($jobs)
            ->name('Sync Privacy Transactions')
            ->allowFailures()
            ->dispatch();

        $this->info(sprintf('Dispatched %d Privacy sync job(s).', count($jobs)));

        return self::SUCCESS;
    }
}
