<?php

declare(strict_types=1);

namespace App\Console\Commands\Article;

use App\Models\User;
use App\Services\Article\ArticleAutomatedTagsRebuildService;
use App\Services\Article\DefaultArticleSocialFeedsService;
use Illuminate\Console\Command;

class ResetDefaultArticleSocialFeedsCommand extends Command
{
    protected $signature = 'article:reset-default-social-feeds
                            {--user= : User id or email (required unless --all)}
                            {--all : Run for all users}
                            {--dry-run : Show what would happen without changing data}';

    protected $description = 'Attach (and regenerate) the default article/news tags and SocialFeeds for a user.';

    public function handle(DefaultArticleSocialFeedsService $service, ArticleAutomatedTagsRebuildService $rebuildService): int
    {
        $userOption = $this->option('user');
        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        if (! $all && (! is_string($userOption) || $userOption === '')) {
            $this->error('You must pass --user=<id|email> or --all.');

            return self::FAILURE;
        }

        $users = $all
            ? User::query()->orderBy('id')->get()
            : collect([$this->resolveUser((string) $userOption)]);

        foreach ($users as $user) {
            $this->line(sprintf('User #%d <%s>', $user->id, $user->email ?? ''));

            if ($dryRun) {
                $this->line('  - [dry-run] Would create/update default article tags and social feeds.');

                continue;
            }

            $result = $service->createOrUpdateForUser($user, $dryRun);

            $this->line(sprintf(
                '  - Tags: %d created, %d updated',
                (int) ($result['created_tags'] ?? 0),
                (int) ($result['updated_tags'] ?? 0),
            ));

            $this->line(sprintf(
                '  - SocialFeeds: %d created, %d updated',
                (int) ($result['created_feeds'] ?? 0),
                (int) ($result['updated_feeds'] ?? 0),
            ));

            $this->line('  - Rebuilding article taggables (delete + re-evaluate)…');
            $rebuild = $rebuildService->rebuildForUser($user);
            $this->line(sprintf(
                '  - Rebuild: %d tags across %d articles | deleted %d taggables | attached %d',
                (int) ($rebuild['tags'] ?? 0),
                (int) ($rebuild['articles'] ?? 0),
                (int) ($rebuild['deleted_taggables'] ?? 0),
                (int) ($rebuild['attached_taggables'] ?? 0),
            ));
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
}
