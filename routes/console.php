<?php

declare(strict_types=1);

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Contracts\Repositories\MatrixClientSyncRepositoryContract;
use App\Contracts\Services\Messaging\MatrixServiceContract;
use App\Jobs\Crm\SyncPersonToMonica;
use App\Jobs\FetchResourcesFromCredentials;
use App\Jobs\MatrixSyncJob;
use App\Jobs\News\UpdateAllFeeds;
use App\Jobs\Notifications\BuildSummaryNotificationJob;
use App\Jobs\SyncJiraTicketsJob;
use App\Models\Credential;
use App\Models\Message;
use App\Models\Person;
use Illuminate\Support\Arr;
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
    MatrixSyncJob::dispatchSync(true);
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

Artisan::command('monica:import {--limit=100} {--page=1}', function (\App\Contracts\Services\Crm\MonicaClientContract $client) {
    $limit = max(1, (int) $this->option('limit'));
    $page = max(1, (int) $this->option('page'));

    /** @var Credential|null $credential */
    $credential = Credential::query()
        ->where('type', Credential::TYPE_CRM)
        ->where('service', Credential::MONICA)
        ->whereNotNull('access_token')
        ->with('user')
        ->first();

    if (! $credential || ! $credential->user) {
        $this->error('No Monica CRM credential with a user was found.');

        return 1;
    }

    $people = Person::all();

    $emailIndex = [];
    $phoneIndex = [];
    $nameIndex = [];

    $normalizePhone = fn (string $value) => preg_replace('/\D+/', '', $value);
    $normalizeEmail = fn (string $value) => strtolower(trim($value));
    $normalizeName = fn (string $value) => strtolower(trim($value));

    $personModel = null;
    foreach ($people as $personModel) {
        $emails = collect($personModel->emails ?? [])
            ->prepend($personModel->primary_email)
            ->filter()
            ->map($normalizeEmail)
            ->unique();

        foreach ($emails as $email) {
            $emailIndex[$email][] = $personModel;
        }

        $phones = collect($personModel->phone_numbers ?? [])
            ->prepend($personModel->primary_number)
            ->filter()
            ->map($normalizePhone)
            ->filter()
            ->unique();

        foreach ($phones as $phone) {
            $phoneIndex[$phone][] = $personModel;
        }

        if ($personModel->name) {
            $nameIndex[$normalizeName($personModel->name)][] = $personModel;
        }
    }

    $linked = 0;
    $skipped = 0;
    $prompted = 0;

    $extractEmails = function (array $contact) use ($normalizeEmail): array {
        $fields = Arr::get($contact, 'contactfields', Arr::get($contact, 'contactFields', []));

        return collect($fields)
            ->filter(function ($field) {
                $type = strtolower((string) (Arr::get($field, 'contact_field_type.type') ?? ''));
                $protocol = strtolower((string) (Arr::get($field, 'contact_field_type.protocol') ?? ''));
                $name = strtolower((string) (Arr::get($field, 'contact_field_type.name') ?? ''));

                return $type === 'email' || str_contains($protocol, 'mailto') || str_contains($name, 'email');
            })
            ->map(fn ($field) => Arr::get($field, 'content'))
            ->filter()
            ->map($normalizeEmail)
            ->unique()
            ->values()
            ->all();
    };

    $extractPhones = function (array $contact) use ($normalizePhone): array {
        $fields = Arr::get($contact, 'contactfields', Arr::get($contact, 'contactFields', []));

        return collect($fields)
            ->filter(function ($field) {
                $type = strtolower((string) (Arr::get($field, 'contact_field_type.type') ?? ''));
                $protocol = strtolower((string) (Arr::get($field, 'contact_field_type.protocol') ?? ''));
                $name = strtolower((string) (Arr::get($field, 'contact_field_type.name') ?? ''));

                return $type === 'phone' || str_contains($protocol, 'tel') || str_contains($name, 'phone');
            })
            ->map(fn ($field) => Arr::get($field, 'content'))
            ->filter()
            ->map($normalizePhone)
            ->filter()
            ->unique()
            ->values()
            ->all();
    };

    $contactName = fn (array $contact): string => trim(
        implode(' ', array_filter([
            Arr::get($contact, 'first_name'),
            Arr::get($contact, 'last_name'),
        ]))
    );

    while (true) {
        $contacts = $client->listContacts($credential, $page, $limit);

        if (empty($contacts)) {
            break;
        }

        foreach ($contacts as $contact) {
            $contactId = Arr::get($contact, 'id');

            if (! $contactId) {
                $this->warn('Skipping contact without id.');
                continue;
            }

            $emails = $extractEmails($contact);
            $phones = $extractPhones($contact);

            $matches = collect();

            foreach ($emails as $email) {
                $matches = $matches->merge($emailIndex[$email] ?? []);
            }

            foreach ($phones as $phone) {
                $matches = $matches->merge($phoneIndex[$phone] ?? []);
            }

            $matches = $matches->unique('id')->values();

            $linkedPerson = null;

            if ($matches->count() === 1) {
                $linkedPerson = $matches->first();
            } elseif ($matches->count() === 0) {
                $byName = $nameIndex[$normalizeName($contactName($contact))] ?? collect();
                if ($byName instanceof \Illuminate\Support\Collection) {
                    $byName = $byName->values();
                } else {
                    $byName = collect($byName);
                }

                $contactDisplay = $contactName($contact) ?: "contact {$contactId}";

                if ($byName->count() === 1) {
                    $candidate = $byName->first();
                    $prompted++;
                    if ($this->confirm("Link Monica contact \"{$contactDisplay}\" ({$contactId}) to Person #{$candidate->id} ({$candidate->name})?")) {
                        $linkedPerson = $candidate;
                    }
                } elseif ($byName->count() > 1) {
                    $prompted++;
                    $choices = $byName->map(function (Person $person) {
                        return "{$person->id}: {$person->name}";
                    })->toArray();

                    $choice = $this->choice(
                        "Multiple matches for Monica contact \"{$contactDisplay}\" ({$contactId}). Choose a Person or None:",
                        array_merge($choices, ['None'])
                    );

                    if ($choice !== 'None') {
                        [$personId] = explode(':', $choice, 2);
                        $linkedPerson = $byName->firstWhere('id', (int) $personId);
                    }
                }
            } else {
                $prompted++;
                $choices = $matches->map(function (Person $person) {
                    return "{$person->id}: {$person->name}";
                })->toArray();

                $choice = $this->choice(
                    "Multiple matches for Monica contact \"{$contactName($contact)}\" ({$contactId}). Choose a Person or None:",
                    array_merge($choices, ['None'])
                );

                if ($choice !== 'None') {
                    [$personId] = explode(':', $choice, 2);
                    $linkedPerson = $matches->firstWhere('id', (int) $personId);
                }
            }

            if (! $linkedPerson) {
                $skipped++;
                continue;
            }

            if ((string) $linkedPerson->monica_contact_id !== (string) $contactId) {
                $linkedPerson->monica_contact_id = (string) $contactId;
                $linkedPerson->saveQuietly();
                $linked++;
                $this->info("Linked Person #{$linkedPerson->id} ({$linkedPerson->name}) to Monica contact \"{$contactName($contact)}\" ({$contactId}).");
            } else {
                $this->line("Person #{$linkedPerson->id} already linked to Monica contact {$contactId}.");
            }
        }

        $page++;
    }

    $this->info("Import finished. Linked: {$linked}; Prompted: {$prompted}; Skipped: {$skipped}");

    return 0;
})->describe('Import Monica contacts and link to local People');
