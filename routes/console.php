<?php

use App\Jobs\FetchResourcesFromCredentials;
use App\Jobs\News\UpdateAllFeeds;
use App\Jobs\Notifications\BuildSummaryNotificationJob;
use App\Jobs\SyncJiraTicketsJob;
use Illuminate\Support\Facades\Schedule;

declare(strict_types=1);


Schedule::job(SyncJiraTicketsJob::class)->daily();
Schedule::job(UpdateAllFeeds::class)->everyFifteenMinutes();
Schedule::job(FetchResourcesFromCredentials::class)->hourly();
Schedule::job(BuildSummaryNotificationJob::class)->dailyAt('13:00');
Schedule::command('operations:queue')->everyFiveMinutes();
