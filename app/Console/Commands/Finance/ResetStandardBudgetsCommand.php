<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Models\Finance\Budget;
use App\Models\User;
use App\Services\Finance\DefaultBudgetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetStandardBudgetsCommand extends Command
{
    protected $signature = 'finance:reset-standard-budgets
                            {--user= : User id or email (required unless --all)}
                            {--all : Run for all users}
                            {--dry-run : Show what would happen without changing data}';

    protected $description = 'Delete and recreate the standard budgets (name-matched) for a user (or all users) using the default budget ratios.';

    public function handle(DefaultBudgetService $defaultBudgetService): int
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
            : collect([$this->resolveUser($userOption)]);

        $standardNames = $defaultBudgetService->standardBudgetNames();

        foreach ($users as $user) {
            $this->line(sprintf('User #%d <%s>', $user->id, $user->email ?? ''));

            $standardBudgets = Budget::query()
                ->where('user_id', $user->id)
                ->whereIn('name', $standardNames)
                ->orderBy('id')
                ->get();

            if ($standardBudgets->isEmpty()) {
                $this->line('  - No standard budgets found; will create fresh.');
            } else {
                $this->line(sprintf('  - Found %d standard budgets to reset.', $standardBudgets->count()));
            }

            if ($dryRun) {
                $this->line(sprintf('  - [dry-run] Would delete %d budgets and their taggables.', $standardBudgets->count()));
                $this->line(sprintf('  - [dry-run] Would create %d standard budgets.', count($standardNames)));

                continue;
            }

            DB::transaction(function () use ($standardBudgets, $user, $defaultBudgetService): void {
                $budgetIds = $standardBudgets->pluck('id')->all();

                if (! empty($budgetIds)) {
                    DB::table('taggables')
                        ->where('taggable_type', Budget::class)
                        ->whereIn('taggable_id', $budgetIds)
                        ->delete();

                    Budget::query()->whereIn('id', $budgetIds)->delete();
                }

                // Recreate standard budgets even if the user still has other (non-standard) budgets.
                $defaultBudgetService->createForUser($user, skipIfAnyBudgetExists: false);
            });
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
