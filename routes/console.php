<?php

declare(strict_types=1);

use App\Jobs\FetchResourcesFromCredentials;
use App\Jobs\MatrixSyncJob;
use App\Jobs\News\UpdateAllFeeds;
use App\Jobs\Notifications\BuildSummaryNotificationJob;
use App\Jobs\SyncJiraTicketsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(SyncJiraTicketsJob::class)->daily();
Schedule::job(UpdateAllFeeds::class)->everyFifteenMinutes();
Schedule::job(FetchResourcesFromCredentials::class)->hourly();
Schedule::job(BuildSummaryNotificationJob::class)->dailyAt('13:00');
Schedule::command('operations:queue')->everyFiveMinutes();
// Temporarily disabled due to the server going down
Schedule::job(MatrixSyncJob::class)->everyMinute();
Artisan::command('update:all-feeds', fn () => UpdateAllFeeds::dispatch())->describe('Update all feeds');
Artisan::command('update:all-credentials', fn () => FetchResourcesFromCredentials::dispatch())->describe('Fetch resources from credentials');
Artisan::command('build:summary-notification', fn () => BuildSummaryNotificationJob::dispatch())->describe('Build summary notification');
Artisan::command('sync:jira-tickets', fn () => SyncJiraTicketsJob::dispatch())->describe('Sync Jira tickets');
Artisan::command('sync:matrix', fn () => MatrixSyncJob::dispatchSync())->describe('Sync Matrix');
Artisan::command('sync:transaction-tags', fn () => dispatch_sync(new \App\Jobs\SyncTagsWithTransactionsInDatabase));
