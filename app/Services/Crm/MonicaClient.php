<?php

declare(strict_types=1);

namespace App\Services\Crm;

use App\Contracts\Services\Crm\MonicaClientContract;
use App\Models\Credential;
use App\Models\Message;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class MonicaClient implements MonicaClientContract
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function findCredentialForUser(User $user): ?Credential
    {
        $service = config('services.monica.service', Credential::MONICA);

        return Credential::query()
            ->where('user_id', $user->id)
            ->where('service', $service)
            ->orderByDesc('enabled_on')
            ->orderByDesc('id')
            ->first();
    }

    public function syncContact(Credential $credential, Person $person): int|string|null
    {
        $payload = $this->buildContactPayload($person);

        if ($payload === []) {
            return null;
        }

        try {
            $response = $person->monica_contact_id
                ? $this->http($credential)->put("/contacts/{$person->monica_contact_id}", $payload)
                : $this->http($credential)->post('/contacts', $payload);

            $response->throw();

            return $this->contactIdFromResponse($response->json()) ?? $person->monica_contact_id;
        } catch (RequestException $exception) {
            $this->logger->warning('Failed syncing contact to Monica', [
                'person_id' => $person->getKey(),
                'monica_contact_id' => $person->monica_contact_id,
                'status' => $exception->response?->status(),
                'body' => $exception->response?->json(),
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function createConversation(
        Credential $credential,
        int|string $contactId,
        Message $message,
        Person $recipient,
    ): bool {
        $payload = [
            'subject' => $this->conversationSubject($message),
            'content' => $message->message ?? $message->html_message ?? '',
            'happened_at' => optional($message->originated_at)->toIso8601String() ?? now()->toIso8601String(),
            'direction' => 'outgoing',
        ];

        try {
            $response = $this->http($credential)->post("/contacts/{$contactId}/conversations", $payload);
            $response->throw();

            return $response->successful();
        } catch (Throwable $exception) {
            $this->logger->warning('Failed creating Monica conversation entry', [
                'person_id' => $recipient->getKey(),
                'monica_contact_id' => $contactId,
                'message_id' => $message->getKey(),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function listContacts(Credential $credential, int $page = 1, int $limit = 100): array
    {
        $response = $this->http($credential)->get('/contacts', [
            'page' => $page,
            'limit' => $limit,
        ]);

        $response->throw();

        $payload = $response->json();
        $data = Arr::get($payload, 'data', []);

        return is_array($data) ? $data : [];
    }

    private function http(Credential $credential): PendingRequest
    {
        $token = trim((string) ($credential->access_token ?? ''));

        if ($token === '') {
            throw new RuntimeException('Monica credential missing access token.');
        }

        $baseUrl = rtrim((string) config('services.monica.base_url', ''), '/');

        return Http::asJson()
            ->acceptJson()
            ->withToken($token)
            ->baseUrl($baseUrl);
    }

    private function buildContactPayload(Person $person): array
    {
        [$firstName, $lastName] = $this->splitName($person->name ?? '');
        $birthdate = $person->birthdate instanceof \DateTimeInterface ? $person->birthdate : null;
        $birthdateKnown = (bool) $birthdate;
        $birthdateDay = $birthdate ? (int) $birthdate->format('d') : null;
        $birthdateMonth = $birthdate ? (int) $birthdate->format('m') : null;
        $birthdateYear = $birthdate ? (int) $birthdate->format('Y') : null;

        return array_filter([
            'first_name' => $firstName ?: ($person->primary_email ?? $person->primary_number ?? $person->name ?? 'Friend'),
            'last_name' => $lastName,
            'nickname' => $person->name,
            'gender_id' => 3, // "Rather not say" default
            'is_deceased' => false,
            'is_deceased_date_known' => false,
            'is_birthdate_known' => $birthdateKnown,
            'birthdate_is_age_based' => false,
            'birthdate_day' => $birthdateDay,
            'birthdate_month' => $birthdateMonth,
            'birthdate_year' => $birthdateYear,
            'is_partial' => false,
            'is_active' => true,
            'is_me' => false,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function splitName(string $name): array
    {
        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', trim($name));

        $first = Arr::first($parts) ?? '';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [$first, $last];
    }

    private function contactIdFromResponse(array $payload): int|string|null
    {
        $candidates = [
            Arr::get($payload, 'data.id'),
            Arr::get($payload, 'id'),
            Arr::get($payload, 'contact.id'),
        ];

        return collect($candidates)
            ->first(fn ($id) => is_string($id) || is_int($id));
    }

    private function conversationSubject(Message $message): string
    {
        $subject = $message->subject ?? '';

        if ($subject !== '') {
            return $subject;
        }

        return Str::limit((string) ($message->message ?? strip_tags((string) $message->html_message)), 120);
    }
}
