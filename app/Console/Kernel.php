<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\FetchResourcesFromCredentials;
use App\Jobs\News\UpdateAllFeeds;
use App\Jobs\Notifications\BuildSummaryNotificationJob;
use App\Jobs\SyncJiraTicketsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(SyncJiraTicketsJob::class)->daily();
        $schedule->job(UpdateAllFeeds::class)->everyFifteenMinutes();
        $schedule->job(FetchResourcesFromCredentials::class)->hourly();
        $schedule->job(BuildSummaryNotificationJob::class)->dailyAt('13:00');
        $schedule->command('operations:queue')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
