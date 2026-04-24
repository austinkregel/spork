<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Credential;
use App\Models\Domain;
use App\Models\Email;
use App\Models\Finance\Account;
use App\Models\Message;
use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class DeduplicateCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:deduplicate {--dry-run : Only report duplicates; do not modify any records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deduplicate credentials with identical secret_fingerprint values within a user scope';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Scanning for duplicate credentials by user and secret_fingerprint...');

        /** @var \Illuminate\Support\Collection<int, array{id:int,user_id:int,service:string,secret_fingerprint:string,count:int}> $groups */
        $groups = Credential::query()
            ->whereNotNull('secret_fingerprint')
            ->selectRaw('MIN(id) as id, user_id, service, secret_fingerprint, COUNT(*) as count')
            ->groupBy('user_id', 'service', 'secret_fingerprint')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate credentials found.');

            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d duplicate credential groups.', $groups->count()));

        foreach ($groups as $group) {
            $this->deduplicateGroup(
                userId: (int) $group['user_id'],
                service: (string) $group['service'],
                fingerprint: (string) $group['secret_fingerprint'],
                canonicalId: (int) $group['id'],
                dryRun: $dryRun,
            );
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Deduplication complete.');

        return Command::SUCCESS;
    }

    private function deduplicateGroup(int $userId, string $service, string $fingerprint, int $canonicalId, bool $dryRun): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int,\App\Models\Credential> $credentials */
        $credentials = Credential::query()
            ->where('user_id', $userId)
            ->where('service', $service)
            ->where('secret_fingerprint', $fingerprint)
            ->orderBy('id')
            ->get();

        if ($credentials->count() <= 1) {
            return;
        }

        /** @var \App\Models\Credential $canonical */
        $canonical = $credentials->firstWhere('id', $canonicalId) ?? $credentials->first();
        /** @var \Illuminate\Database\Eloquent\Collection<int,\App\Models\Credential> $duplicates */
        $duplicates = $credentials->where('id', '!=', $canonical->id);

        $this->line(sprintf(
            'User %d, service %s, fingerprint %s: canonical #%d, %d duplicates',
            $userId,
            $service,
            substr($fingerprint, 0, 8).'…',
            $canonical->id,
            $duplicates->count(),
        ));

        if ($dryRun) {
            return;
        }

        $this->repointRelations($canonical, $duplicates);

        Credential::query()
            ->whereIn('id', $duplicates->pluck('id'))
            ->delete();
    }

    /**
     * Repoint known relationships that reference credentials.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int,\App\Models\Credential>  $duplicates
     */
    private function repointRelations(Credential $canonical, Collection $duplicates): void
    {
        $duplicateIds = $duplicates->pluck('id')->all();

        Domain::query()
            ->whereIn('credential_id', $duplicateIds)
            ->update(['credential_id' => $canonical->id]);

        Server::query()
            ->whereIn('credential_id', $duplicateIds)
            ->update(['credential_id' => $canonical->id]);

        Account::query()
            ->whereIn('credential_id', $duplicateIds)
            ->update(['credential_id' => $canonical->id]);

        Message::query()
            ->whereIn('credential_id', $duplicateIds)
            ->update(['credential_id' => $canonical->id]);

        Email::query()
            ->whereIn('credential_id', $duplicateIds)
            ->update(['credential_id' => $canonical->id]);
    }
}
