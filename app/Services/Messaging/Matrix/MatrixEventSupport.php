<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix;

use App\Models\Credential;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Person;
use App\Models\Thread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class MatrixEventSupport
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function extractDeviceId(string $eventType): string
    {
        $parts = explode('.', $eventType);

        return (string) Arr::last($parts);
    }

    public function ignored(array $event, string $reason, ?string $handler = null, array $extra = []): void
    {
        $this->logger->info('Ignoring Matrix event', array_merge([
            'type' => $event['type'] ?? 'unknown',
            'event_id' => $event['event_id'] ?? null,
            'handler' => $handler,
            'reason' => $reason,
        ], $extra));
    }

    public function downloadMedia(Credential $credential, string $mxcUrl): ?string
    {
        $mxcUrl = Str::after($mxcUrl, 'mxc://');

        [$domain, $key] = explode('/', $mxcUrl);

        try {
            $response = Http::withHeaders([
                'Accept' => '*/*',
                'Authorization' => 'Bearer '.$credential->access_token,
            ])->get(
                $credential->settings['matrix_server'].sprintf('/_matrix/client/v1/media/thumbnail/%s?timeout_ms=500&width=64&height=64', $mxcUrl)
            )->body();

            Storage::disk('public')->put($key, $response);

            // Keep this as a path (not a full URL) for consistency across the app/tests.
            return '/storage/'.$key;
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to download Matrix media', [
                'exception' => $exception->getMessage(),
                'media' => $mxcUrl,
                'domain' => $domain ?? null,
            ]);

            return null;
        }
    }

    public function findOrCreatePerson(string $identifier, User $user): Person
    {
        $person = Person::whereJsonContains('identifiers', $identifier)->first();

        if (! $person) {
            $person = Person::create([
                'name' => $identifier,
                'identifiers' => [$identifier],
                'user_id' => $user->id ?? 1,
            ]);
        }

        return $person;
    }

    public function redactMessage(array $event): void
    {
        $targetEventId = $event['redacts'] ?? null;

        if (! $targetEventId) {
            $this->ignored($event, 'redaction_missing_reference', __METHOD__);

            return;
        }

        /** @var Message|null $message */
        $message = Message::firstWhere('event_id', $targetEventId);

        if (! $message) {
            $reaction = MessageReaction::firstWhere('matrix_event_id', $targetEventId);

            if ($reaction) {
                $reaction->delete();
            } else {
                $this->ignored($event, 'redaction_target_missing', __METHOD__);
            }

            return;
        }

        $redactionMessage = $event['content']['reason'] ?? 'Message redacted';
        $timestamp = Carbon::createFromFormat('U', (string) (int) round(($event['origin_server_ts'] ?? 0) / 1000));

        $message->update([
            'message' => '🗑️ '.$redactionMessage,
            'html_message' => '<i>🗑️ '.$redactionMessage.'</i>',
            'thumbnail_url' => null,
            'originated_at' => $timestamp,
        ]);
    }

    public function renameThreadToOtherParticipant(?Thread $thread, User $user): void
    {
        if (! $thread || ! $this->shouldRenameThread($thread)) {
            return;
        }

        $thread->loadMissing('participants');
        $participants = $thread->participants;

        if ($participants->count() === 0) {
            return;
        }

        if ($participants->count() === 1) {
            $candidate = $participants->first();

            if ($this->isUserParticipant($candidate, $user)) {
                return;
            }

            $thread->update(['name' => $this->resolvePersonDisplayName($candidate)]);

            return;
        }

        if ($participants->count() !== 2) {
            return;
        }

        $userIdentifiers = collect($user->person?->identifiers ?? [])
            ->filter()
            ->map(fn ($identifier) => strtolower($identifier))
            ->values();

        $otherParticipant = $participants->first(function (Person $participant) use ($user, $userIdentifiers) {
            if ($this->isUserParticipant($participant, $user)) {
                return false;
            }

            if ($userIdentifiers->isEmpty()) {
                return true;
            }

            $participantIdentifiers = collect($participant->identifiers ?? [])
                ->filter()
                ->map(fn ($identifier) => strtolower($identifier));

            return $participantIdentifiers->intersect($userIdentifiers)->isEmpty();
        });

        if (! $otherParticipant) {
            return;
        }

        $thread->update(['name' => $this->resolvePersonDisplayName($otherParticipant)]);
    }

    protected function shouldRenameThread(Thread $thread): bool
    {
        $name = $thread->name;

        if (is_array($name)) {
            $name = Arr::first($name);
        }

        if (blank($name)) {
            return true;
        }

        if (! is_string($name)) {
            return false;
        }

        return str_starts_with($name, '!');
    }

    protected function resolvePersonDisplayName(Person $person): string
    {
        $name = trim((string) ($person->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $identifiers = $person->identifiers ?? [];

        if (! empty($identifiers)) {
            return Arr::first($identifiers);
        }

        return 'Conversation';
    }

    public function getMatrixUserName(): string
    {
        return '@'.env('MATRIX_USERNAME').':'.parse_url((string) env('MATRIX_HOST'), PHP_URL_HOST);
    }

    protected function isUserParticipant(Person $participant, User $user): bool
    {
        if ($user->person && $participant->is($user->person)) {
            return true;
        }

        return (int) $participant->user_id === (int) $user->id;
    }
}
