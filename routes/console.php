<?php

declare(strict_types=1);

use App\Jobs\FetchResourcesFromCredentials;
use App\Jobs\MatrixSyncJob;
use App\Jobs\News\UpdateAllFeeds;
use App\Jobs\Notifications\BuildSummaryNotificationJob;
use App\Jobs\SyncJiraTicketsJob;
use App\Models\Finance\Budget;
use App\Models\Tag;
use Illuminate\Support\Facades\Schedule;

Schedule::job(SyncJiraTicketsJob::class)->daily();
Schedule::job(UpdateAllFeeds::class)->everyFifteenMinutes();
Schedule::job(FetchResourcesFromCredentials::class)->hourly();
Schedule::job(BuildSummaryNotificationJob::class)->dailyAt('13:00');
Schedule::command('operations:queue')->everyFiveMinutes();
Schedule::job(MatrixSyncJob::class)->everyFiveMinutes();

Artisan::command('update:all-feeds', function() {
    UpdateAllFeeds::dispatch();
})->describe('Update all feeds');
Artisan::command('update:all-credentials', function() {
    FetchResourcesFromCredentials::dispatch();
})->describe('Fetch resources from credentials');
Artisan::command('build:summary-notification', function() {
    BuildSummaryNotificationJob::dispatch();
})->describe('Build summary notification');
Artisan::command('sync:jira-tickets', function() {
    SyncJiraTicketsJob::dispatch();
})->describe('Sync Jira tickets');
Artisan::command('sync:matrix', function() {
    MatrixSyncJob::dispatch();
})->describe('Sync Matrix');
Artisan::command('sync:transaction-tags', function () {
    dispatch_sync(new \App\Jobs\SyncTagsWithTransactionsInDatabase());
});
