<?php

declare(strict_types=1);

namespace App\Console\Commands\Automations;

use App\Models\Automation;
use App\Operations\AutomationOperation;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SeedNextRuns extends Command
{
    protected $signature = 'automations:seed-next';

    protected $description = 'Ensure at least one upcoming AutomationOperation exists for each enabled Automation';

    public function handle(): int
    {
        $now = Carbon::now();
        Automation::query()
            ->where('enabled', true)
            ->whereNotNull('cron_expression')
            ->each(function (Automation $automation) use ($now): void {
                $tz = $automation->timezone ?: config('app.timezone');
                $cron = CronExpression::factory($automation->cron_expression);
                $next = Carbon::instance($cron->getNextRunDate($now, 0, false, $tz));

                $exists = AutomationOperation::query()
                    ->where('automation_id', $automation->getKey())
                    ->where('should_run_at', '>=', $now)
                    ->exists();

                if (! $exists) {
                    AutomationOperation::create([
                        'automation_id' => $automation->getKey(),
                        'should_run_at' => $next,
                    ]);
                }
            });

        $this->info('Seeded next AutomationOperation where missing.');

        return self::SUCCESS;
    }
}


