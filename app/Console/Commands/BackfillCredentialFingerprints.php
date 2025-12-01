<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Credential;
use Illuminate\Console\Command;

class BackfillCredentialFingerprints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:fingerprint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill secret_fingerprint for existing credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Backfilling credential secret_fingerprint values...');

        Credential::query()
            ->whereNull('secret_fingerprint')
            ->orderBy('id')
            ->chunkById(100, function ($credentials): void {
                /** @var \App\Models\Credential $credential */
                foreach ($credentials as $credential) {
                    $original = $credential->getOriginal('secret_fingerprint');

                    $credential->save();

                    if ($original !== $credential->secret_fingerprint) {
                        $this->line(sprintf(
                            'Credential #%d updated',
                            $credential->id,
                        ));
                    }
                }
            });

        $this->info('Backfill completed.');

        return Command::SUCCESS;
    }
}



