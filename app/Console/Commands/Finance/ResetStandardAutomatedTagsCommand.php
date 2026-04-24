<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Listeners\Finance\CreateDefaultAutomatedTags;
use App\Models\Condition;
use App\Models\Tag;
use App\Models\User;
use App\Services\Finance\AutomatedTagsRebuildService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetStandardAutomatedTagsCommand extends Command
{
    protected $signature = 'finance:reset-standard-automated-tags
                            {--user= : User id or email (required unless --all)}
                            {--all : Run for all users}
                            {--dry-run : Show what would happen without changing data}
                            {--force-shared : Allow deleting tags that appear to be shared across users}
                            {--no-rebuild : Skip re-applying tags to rebuild taggables}
                            {--reset-budgets : After tags are reset, run finance:reset-standard-budgets for the same user scope}';

    protected $description = 'Delete and recreate the standard automatic tags (and their conditions/taggables), then rebuild taggables by re-applying tags.';

    public function handle(AutomatedTagsRebuildService $rebuildService): int
    {
        $userOption = $this->option('user');
        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');
        $forceShared = (bool) $this->option('force-shared');
        $noRebuild = (bool) $this->option('no-rebuild');

        if (! $all && (! is_string($userOption) || $userOption === '')) {
            $this->error('You must pass --user=<id|email> or --all.');

            return self::FAILURE;
        }

        $users = $all
            ? User::query()->orderBy('id')->get()
            : collect([$this->resolveUser($userOption)]);

        $definitions = CreateDefaultAutomatedTags::standardTagDefinitions();
        $names = array_values(array_map(fn (array $def) => (string) ($def['name'] ?? ''), $definitions));
        $slugs = array_values(array_unique(array_map(fn (string $name) => Str::slug($name), $names)));

        foreach ($users as $user) {
            $this->line(sprintf('User #%d <%s>', $user->id, $user->email ?? ''));

            $standardTags = $user->tags()
                ->where('type', 'automatic')
                ->where(function ($q) use ($names, $slugs) {
                    $q->whereIn('name->en', $names)->orWhereIn('slug->en', $slugs);
                })
                ->with('conditions')
                ->get();

            if ($standardTags->isEmpty()) {
                $this->line('  - No standard tags found; will create fresh.');
            } else {
                $this->line(sprintf('  - Found %d standard tags to reset.', $standardTags->count()));
            }

            foreach ($standardTags as $tag) {
                $isShared = DB::table('taggables')
                    ->where('tag_id', $tag->id)
                    ->where('taggable_type', User::class)
                    ->where('taggable_id', '!=', $user->id)
                    ->exists();

                if ($isShared && ! $forceShared) {
                    $this->error(sprintf(
                        '  - Refusing to delete shared tag #%d (%s). Re-run with --force-shared to override.',
                        $tag->id,
                        (string) ($tag->name['en'] ?? $tag->name ?? $tag->id)
                    ));

                    return self::FAILURE;
                }

                if ($dryRun) {
                    $this->line(sprintf('  - [dry-run] Delete tag #%d and its taggables/conditions.', $tag->id));

                    continue;
                }

                DB::transaction(function () use ($tag): void {
                    DB::table('taggables')->where('tag_id', $tag->id)->delete();
                    $tag->conditions()->delete();
                    $tag->delete();
                });
            }

            if ($dryRun) {
                $this->line(sprintf('  - [dry-run] Would create %d standard tags.', count($definitions)));
            } else {
                foreach ($definitions as $def) {
                    $tag = $this->createStandardTagForUser($user, $def);
                    $this->line(sprintf('  - Created tag #%d %s', $tag->id, (string) ($tag->name['en'] ?? $tag->name ?? '')));
                }
            }
            if ((bool) $this->option('reset-budgets')) {
                if ($dryRun) {
                    $this->line('  - [dry-run] Would reset standard budgets for this user.');
                } else {
                    $this->line('  - Resetting standard budgets…');
                    $this->call('finance:reset-standard-budgets', [
                        '--user' => (string) $user->id,
                    ]);
                }
            }

            if (! $noRebuild) {
                if ($dryRun) {
                    $this->line('  - [dry-run] Would rebuild taggables by re-applying tags.');
                } else {
                    $this->line('  - Rebuilding taggables by re-applying tags…');
                    $rebuildService->rebuildForUser($user);
                }
            }
        }

        $this->info('Done.');

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

    /**
     * @param  array{name:string,type?:string,must_all_conditions_pass?:bool,conditions?:array<int, array{parameter:string,comparator:string,value:mixed}>}  $def
     */
    protected function createStandardTagForUser(User $user, array $def): Tag
    {
        $name = (string) $def['name'];
        $type = (string) ($def['type'] ?? 'automatic');
        $mustAll = (bool) ($def['must_all_conditions_pass'] ?? false);
        $conditions = $def['conditions'] ?? [];

        return DB::transaction(function () use ($user, $name, $type, $mustAll, $conditions): Tag {
            /** @var Tag $tag */
            $tag = Tag::query()->create([
                'name' => ['en' => $name],
                'slug' => ['en' => Str::slug($name)],
                'type' => $type,
                'must_all_conditions_pass' => $mustAll,
            ]);

            $user->tags()->syncWithoutDetaching([$tag->getKey()]);

            foreach ($conditions as $condition) {
                $tag->conditions()->create([
                    'parameter' => (string) ($condition['parameter'] ?? ''),
                    'comparator' => (string) ($condition['comparator'] ?? Condition::COMPARATOR_LIKE),
                    'value' => $condition['value'] ?? null,
                ]);
            }

            return $tag;
        });
    }
}
