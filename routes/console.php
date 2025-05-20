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
Schedule::job(MatrixSyncJob::class)->everyMinute();

Artisan::command('update:all-feeds', function () {
    UpdateAllFeeds::dispatch();
})->describe('Update all feeds');
Artisan::command('update:all-credentials', function () {
    FetchResourcesFromCredentials::dispatch();
})->describe('Fetch resources from credentials');
Artisan::command('build:summary-notification', function () {
    BuildSummaryNotificationJob::dispatch();
})->describe('Build summary notification');
Artisan::command('sync:jira-tickets', function () {
    SyncJiraTicketsJob::dispatch();
})->describe('Sync Jira tickets');
//Artisan::command('sync:matrix', function () {
// Temporarily disabled due to the server going down
//    MatrixSyncJob::dispatchSync();
//})->describe('Sync Matrix');
Artisan::command('sync:transaction-tags', function () {
    dispatch_sync(new \App\Jobs\SyncTagsWithTransactionsInDatabase);
});

Artisan::command('test', function () {
    $template = view('docker.basic-service', [
        'projects' => [
            [
                'path' => 'spork',
                'queue' => true,
                'web' => true,
                'websocket' => true,
                'cron' => true,
                'image' => 'ghcr.io/austinkregel/spork:latest',
                'dependencies' => [
                ],
                'command' => 'bash /var/www/artisan'
            ],
            [
                'path' => 'lazy.build',
                'queue' => false,
                'web' => true,
                'websocket' => false,
                'cron' => false,
                'volumes' => [],
                'image' => 'ghcr.io/austinkregel/lazy.build:latest',
                'dependencies' => [
                ],
            ],
            [
                'path' => 'cannabis-consumer-information',
                'queue' => true,
                'web' => true,
                'websocket' => false,
                'cron' => true,
                'image' => 'ghcr.io/austinkregel/cannabis-consumer-information:latest',
                'dependencies' => [
                ],
            ],
            [
                'path' => 'aut.hair',
                'queue' => false,
                'web' => true,
                'websocket' => false,
                'cron' => false,
                'image' => 'ghcr.io/austinkregel/aut.hair:latest',
                'dependencies' => [
                ],
            ],
        ]
    ]);
file_put_contents('template.yml', $template->toHtml());
});
