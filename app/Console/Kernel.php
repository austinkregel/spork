<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\FetchCloudflareAnalytics;
use App\Jobs\FetchResourcesFromCredentials;
use App\Jobs\News\UpdateAllFeeds;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(UpdateAllFeeds::class)->hourly();
        $schedule->job(FetchResourcesFromCredentials::class)->everyOddHour();
        $schedule->job(FetchCloudflareAnalytics::class)->everyFourHours();

//        $schedule->command('operations:queue')->everyFiveMinutes();
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
