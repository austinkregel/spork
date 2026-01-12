<?php

declare(strict_types=1);

namespace App\Console\Commands\Messaging;

use App\Models\Email;
use App\Services\Messaging\EmailBodySanitizer;
use App\Services\Messaging\ImapFactoryService;
use Illuminate\Console\Command;

class BackfillEmailMessageText extends Command
{
    protected $signature = 'spork:emails:backfill-message-text
        {--credential-id= : Only backfill emails for this credential id}
        {--since= : Only backfill emails with sent_at >= YYYY-MM-DD}
        {--limit= : Max number of emails to backfill}
        {--dry-run : Do not persist changes}';

    protected $description = 'Backfill emails.message_text by re-fetching bodies via IMAP and storing a safe, size-capped plain-text version.';

    public function handle(ImapFactoryService $imapFactory, EmailBodySanitizer $sanitizer): int
    {
        $query = Email::query()
            ->where(function ($q) {
                $q->whereNull('message_text')->orWhere('message_text', '');
            })
            ->with('credential')
            ->orderBy('id');

        if ($credentialId = $this->option('credential-id')) {
            $query->where('credential_id', (int) $credentialId);
        }

        if ($since = $this->option('since')) {
            $query->whereDate('sent_at', '>=', (string) $since);
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $dryRun = (bool) $this->option('dry-run');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No emails need backfilling.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Backfilling %d emails%s...', $total, $dryRun ? ' (dry-run)' : ''));
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunkById(50, function ($emails) use ($imapFactory, $sanitizer, $dryRun, &$updated, &$skipped, &$failed, $bar): void {
            foreach ($emails as $email) {
                $bar->advance();

                if (! $email->credential || empty($email->email_id)) {
                    $skipped++;

                    continue;
                }

                try {
                    $imap = $imapFactory->make($email->credential);
                    $body = $imap->findMessage((string) $email->email_id);
                    $safeText = $sanitizer->safeTextFromBase64($body['body'] ?? null);

                    if ($safeText === null) {
                        $skipped++;

                        continue;
                    }

                    if (! $dryRun) {
                        $email->forceFill(['message_text' => $safeText])->saveQuietly();
                    }

                    $updated++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf('Done. updated=%d skipped=%d failed=%d', $updated, $skipped, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
