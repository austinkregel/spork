<?php

declare(strict_types=1);

namespace App\Console\Commands\Messaging;

use App\Models\Person;
use App\Models\Thread;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RenameDirectMessageThreads extends Command
{
    protected $signature = 'spork:threads-rename-dms
        {--ignore= : Participant name to ignore (case-insensitive)}
        {--execute : Persist changes (default is dry-run)}
        {--chunk=200 : Chunk size for scanning threads}
        {--limit=0 : Max number of threads to rename (0 = unlimited)}';

    protected $description = 'Rename 2-participant threads to the name of the non-ignored participant.';

    public function handle(): int
    {
        $ignore = $this->normalizeName((string) $this->option('ignore'));
        $execute = (bool) $this->option('execute');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));

        if ($ignore === '') {
            $this->error('The --ignore option cannot be empty.');

            return self::FAILURE;
        }

        $renamed = 0;
        $skipped = 0;

        $query = Thread::query()
            ->with('participants', function ($query) use ($ignore) {
                $query->where('name', 'not like', $ignore);
            })
            ->with(['participants:id,name,identifiers'])
            ->orderBy('id');

        $this->info(sprintf(
            'Scanning 2-participant threads (%s). Ignore=%s',
            $execute ? 'EXECUTE' : 'DRY-RUN',
            $this->option('ignore'),
            
        ));

        $query->chunkById($chunkSize, function (Collection $threads) use (
            $ignore,
            $execute,
            $limit,
            &$renamed,
            &$skipped
        ): bool {
            foreach ($threads as $thread) {
                if ($limit > 0 && $renamed >= $limit) {
                    return false;
                }

                /** @var Thread $thread */
                $thread->loadMissing('participants');

                if ($thread->participants->count() !== 2) {
                    $skipped++;
                    $this->warn(sprintf('Thread #%d has %d participants, skipping (participants_count=%d).', $thread->id, $thread->participants->count(), $thread->participants->count()));
                    continue;
                }

                if (! $this->shouldRenameThread($thread)) {
                    $skipped++;
                    $this->warn(sprintf('Thread #%d is not a placeholder, skipping.', $thread->id));
                    continue;
                }

                $newName = $this->resolveOtherParticipantName(
                    $thread->participants,
                    $ignore,
                );

                if ($newName === null) {
                    $skipped++;
                    $this->warn(sprintf('Thread #%d has the same name as the other participant, skipping (current_name=%s, new_name=%s).', $thread->id, $currentName, $newName));
                    continue;
                }

                $currentName = $this->normalizeThreadName($thread->name);
                if ($currentName !== null && $this->normalizeName($currentName) === $this->normalizeName($newName)) {
                    $skipped++;
                    continue;
                }

                if ($execute) {
                    $thread->update(['name' => $newName]);
                    $this->info(sprintf('Thread #%d renamed to "%s"; %s', $thread->id, $newName, $thread->participants->pluck('name')->implode(', ')));
                }

                $renamed++;

                $this->line(sprintf(
                    '[%s] Thread #%d (%s) => "%s"',
                    $execute ? 'renamed' : 'would-rename',
                    $thread->id,
                    $thread->thread_id ?? 'no-thread-id',
                    $newName,
                ));
            }

            return true;
        });

        $this->newLine();
        $this->info(sprintf('Done. %d renamed, %d skipped.', $renamed, $skipped));

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Person>  $participants
     */
    protected function resolveOtherParticipantName(Collection $participants, string $ignore): ?string
    {
        $ignoreParticipant = $participants->first(function (Person $participant) use ($ignore) {
            return $this->normalizeName((string) ($participant->name ?? '')) === $ignore;
        });

        if (! $ignoreParticipant) {
            return null;
        }

        $otherParticipant = $participants->first(fn (Person $p) => $p->isNot($ignoreParticipant));
        if (! $otherParticipant) {
            return null;
        }

        $resolved = $this->resolvePersonDisplayName($otherParticipant);

        return $resolved !== '' ? $resolved : null;
    }

    protected function resolvePersonDisplayName(Person $person): string
    {
        $name = trim((string) ($person->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $identifiers = $person->identifiers ?? [];
        if (is_array($identifiers)) {
            $first = Arr::first($identifiers);
            if (is_string($first) && trim($first) !== '') {
                return trim($first);
            }
        }

        return '';
    }

    protected function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }

    protected function normalizeThreadName(mixed $name): ?string
    {
        if (is_string($name)) {
            return $name;
        }

        if (is_array($name)) {
            $first = Arr::first($name);

            return is_string($first) ? $first : null;
        }

        return null;
    }

    protected function shouldRenameThread(Thread $thread): bool
    {
        $name = $this->normalizeThreadName($thread->name);

        if ($name === null) {
            return false;
        }

        if (blank($name)) {
            return true;
        }

        return str_starts_with($name, '!');
    }
}


