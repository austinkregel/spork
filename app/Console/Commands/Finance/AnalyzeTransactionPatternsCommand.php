<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeTransactionPatternsCommand extends Command
{
    protected $signature = 'finance:analyze-transaction-patterns
                            {--user= : User id or email (optional; defaults to all users)}
                            {--months=6 : How many months back to include (0 = all time)}
                            {--limit=25 : Max rows to show per section}';

    protected $description = 'Analyze Plaid transaction patterns (personal_finance_category, detailed category, and merchant name) to help refine automated tag conditions.';

    public function handle(): int
    {
        $userOption = $this->option('user');
        $months = max(0, (int) $this->option('months'));
        $limit = max(5, (int) $this->option('limit'));

        $user = is_string($userOption) && $userOption !== '' ? $this->resolveUser($userOption) : null;

        $this->info('Analyzing transaction patterns…');
        $this->line($user ? sprintf('Scope: user #%d <%s>', $user->id, $user->email ?? '') : 'Scope: all users');
        $this->line($months > 0 ? sprintf('Window: last %d month(s)', $months) : 'Window: all time');

        $baseQuery = DB::table('transactions')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.account_id')
            ->join('credentials', 'accounts.credential_id', '=', 'credentials.id')
            ->when($user, fn ($q) => $q->where('credentials.user_id', '=', $user->id))
            ->when($months > 0, fn ($q) => $q->where('transactions.date', '>=', now('UTC')->subMonths($months)->startOfDay()->toDateString()));

        $this->sectionHeader('Top personal_finance_category (count + total)');
        $categories = (clone $baseQuery)
            ->selectRaw('transactions.personal_finance_category as category, COUNT(*) as cnt, SUM(transactions.amount) as total')
            ->whereNotNull('transactions.personal_finance_category')
            ->groupBy('transactions.personal_finance_category')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $this->table(['category', 'count', 'total'], $categories->map(fn ($r) => [
            (string) ($r->category ?? ''),
            (int) $r->cnt,
            (float) $r->total,
        ])->all());

        $this->sectionHeader('Top personal_finance_category_detailed (count + total)');
        $detailed = (clone $baseQuery)
            ->selectRaw('transactions.personal_finance_category_detailed as detailed, COUNT(*) as cnt, SUM(transactions.amount) as total')
            ->whereNotNull('transactions.personal_finance_category_detailed')
            ->groupBy('transactions.personal_finance_category_detailed')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $this->table(['detailed', 'count', 'total'], $detailed->map(fn ($r) => [
            (string) ($r->detailed ?? ''),
            (int) $r->cnt,
            (float) $r->total,
        ])->all());

        $this->sectionHeader('Top merchant names (count + total)');
        $merchants = (clone $baseQuery)
            ->selectRaw('transactions.name as name, COUNT(*) as cnt, SUM(transactions.amount) as total')
            ->whereNotNull('transactions.name')
            ->where('transactions.name', '!=', '')
            ->groupBy('transactions.name')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $this->table(['name', 'count', 'total'], $merchants->map(fn ($r) => [
            (string) ($r->name ?? ''),
            (int) $r->cnt,
            (float) $r->total,
        ])->all());

        $this->sectionHeader('Suggested seeds for Transportation tag');
        $this->line('Look for these personal_finance_category values appearing frequently for your dataset:');
        $this->line('- GAS_STATIONS, AUTOMOTIVE, PUBLIC_TRANSPORTATION, TAXICABS_AND_RIDE_SHARES, PARKING');

        $this->sectionHeader('Suggested seeds for Personal/Household tag');
        $this->line('Look for these personal_finance_category values appearing frequently for your dataset:');
        $this->line('- GENERAL_MERCHANDISE, CLOTHING_AND_ACCESSORIES, HOME_AND_GARDEN, PERSONAL_CARE');

        $this->info('Done.');

        return self::SUCCESS;
    }

    protected function sectionHeader(string $title): void
    {
        $this->newLine();
        $this->line('== '.$title.' ==');
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
