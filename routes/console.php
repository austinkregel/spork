<?php

declare(strict_types=1);

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Contracts\Repositories\MatrixClientSyncRepositoryContract;
use App\Contracts\Services\Messaging\MatrixServiceContract;
use App\Jobs\FetchResourcesFromCredentials;
use App\Jobs\MatrixSyncJob;
use App\Jobs\News\UpdateAllFeeds;
use App\Jobs\Notifications\BuildSummaryNotificationJob;
use App\Jobs\SyncJiraTicketsJob;
use App\Models\Credential;
use App\Models\Message;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(UpdateAllFeeds::class)->everyFifteenMinutes();
Schedule::job(FetchResourcesFromCredentials::class)->hourly();
Schedule::job(BuildSummaryNotificationJob::class)->dailyAt('13:00');
Schedule::command('operations:queue')->everyMinute();
Schedule::command('automations:seed-next')->everyMinute();
// Temporarily disabled due to the server going down
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
Artisan::command('sync:matrix', function () {
    MatrixSyncJob::dispatchSync();
})->describe('Sync Matrix');
Artisan::command('sync:transaction-tags', function () {
    dispatch_sync(new \App\Jobs\SyncTagsWithTransactionsInDatabase);
});

Artisan::command('matrix:inspect {eventId}', function (string $eventId) {
    /** @var MatrixServiceContract $service */
    $service = app(MatrixServiceContract::class);
    $event = $service->fetchEvent($eventId);

    if (! $event) {
        $this->error('Matrix event not found.');

        return 1;
    }

    $roomId = $event['room_id'] ?? null;

    if (! $roomId) {
        $this->error('Matrix event is missing a room_id.');

        return 1;
    }

    /** @var CredentialRepositoryContract $credentialRepository */
    $credentialRepository = app(CredentialRepositoryContract::class);
    /** @var Credential|null $credential */
    $credential = collect($credentialRepository->findAllOfType(Credential::TYPE_MATRIX)->items())->first();

    if (! $credential || ! $credential->user) {
        $this->error('A Matrix credential with an associated user is required.');

        return 1;
    }

    /** @var MatrixClientSyncRepositoryContract $repository */
    $repository = app(MatrixClientSyncRepositoryContract::class);

    $repository->processRoom($roomId, [
        'state' => ['events' => []],
        'timeline' => ['events' => [$event]],
    ], $credential, $credential->user);

    /** @var Message|null $message */
    $message = Message::firstWhere('event_id', $eventId);

    if ($message) {
        $this->info(sprintf('Matrix event %s reprocessed (message #%d).', $eventId, $message->id));
    } else {
        $this->warn(sprintf('Matrix event %s was processed but no message was stored.', $eventId));
    }

    return 0;
})->describe('Fetch a Matrix event and reprocess it through the message handlers');