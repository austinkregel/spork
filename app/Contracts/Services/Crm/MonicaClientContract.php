<?php

declare(strict_types=1);

namespace App\Contracts\Services\Crm;

use App\Models\Credential;
use App\Models\Message;
use App\Models\Person;
use App\Models\User;

interface MonicaClientContract
{
    /**
     * Resolve the Monica credential for a given user, if any.
     */
    public function findCredentialForUser(User $user): ?Credential;

    /**
     * Create or update a contact in Monica for the provided person.
     *
     * @return int|string|null The Monica contact identifier when available.
     */
    public function syncContact(Credential $credential, Person $person): int|string|null;

    /**
     * Append a conversation entry for the given contact.
     */
    public function createConversation(
        Credential $credential,
        int|string $contactId,
        Message $message,
        Person $recipient
    ): bool;

    /**
     * Fetch paginated contacts from Monica.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listContacts(Credential $credential, int $page = 1, int $limit = 100): array;
}
