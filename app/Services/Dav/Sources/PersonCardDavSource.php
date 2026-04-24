<?php

declare(strict_types=1);

namespace App\Services\Dav\Sources;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;

class PersonCardDavSource extends AbstractEloquentSource
{
    public const UID_KEY = 'vcard_uid';

    public function modelClass(): string
    {
        return Person::class;
    }

    public function collectionType(): string
    {
        return self::TYPE_ADDRESSBOOK;
    }

    public function putObject(User $user, string $collection, string $uri, string $body): string
    {
        $vcard = Reader::read($body);

        if (! $vcard instanceof VCard) {
            throw new \Sabre\DAV\Exception\UnsupportedMediaType('Body is not a vCard');
        }

        $uid = (string) ($vcard->UID ?? $this->stripUriExtension($uri));
        $uid = $uid === '' ? (string) Str::uuid() : $uid;

        $person = Person::query()
            ->where('user_id', $user->getKey())
            ->where('identifiers->'.self::UID_KEY, $uid)
            ->first();

        if (! $person) {
            $person = new Person;
            $person->user_id = $user->getKey();
        }

        $this->applyVCardToPerson($vcard, $person, $uid);

        $person->save();

        $this->syncCollectionTag($person, $collection);

        return $this->etagFor($person->fresh());
    }

    protected function defaultDisplayName(): string
    {
        return 'Contacts';
    }

    protected function defaultDescription(): string
    {
        return 'Default contacts address book';
    }

    protected function uidColumn(): string
    {
        return 'identifiers';
    }

    protected function uidJsonKey(): string
    {
        return self::UID_KEY;
    }

    protected function objectUriFor(Model $model): string
    {
        $identifiers = (array) ($model->identifiers ?? []);
        $uid = $identifiers[self::UID_KEY] ?? null;

        if (! $uid) {
            $uid = (string) Str::uuid();
            $identifiers[self::UID_KEY] = $uid;
            $model->identifiers = $identifiers;
            $model->saveQuietly();
        }

        return $uid.'.vcf';
    }

    protected function renderObjectBody(Model $model): string
    {
        return $this->personToVCard($model)->serialize();
    }

    protected function stripUriExtension(string $uri): string
    {
        if (str_ends_with($uri, '.vcf')) {
            return substr($uri, 0, -4);
        }

        return $uri;
    }

    private function personToVCard(Person $person): VCard
    {
        $identifiers = (array) ($person->identifiers ?? []);
        $uid = $identifiers[self::UID_KEY] ?? null;

        if (! $uid) {
            $uid = (string) Str::uuid();
            $identifiers[self::UID_KEY] = $uid;
            $person->identifiers = $identifiers;
            $person->saveQuietly();
        }

        $card = new VCard([
            'VERSION' => '4.0',
            'UID' => $uid,
            'FN' => (string) ($person->name ?? ''),
            'REV' => $person->updated_at?->copy()->utc()->format('Ymd\\THis\\Z'),
        ]);

        $names = (array) ($person->names ?? []);
        $primaryName = $names[0] ?? null;
        if (is_array($primaryName)) {
            $card->add('N', [
                (string) ($primaryName['family'] ?? ''),
                (string) ($primaryName['given'] ?? ''),
                (string) ($primaryName['additional'] ?? ''),
                (string) ($primaryName['prefix'] ?? ''),
                (string) ($primaryName['suffix'] ?? ''),
            ]);

            if (! empty($primaryName['nickname'])) {
                $card->add('NICKNAME', (string) $primaryName['nickname']);
            }
        } elseif (! empty($person->name)) {
            $parts = explode(' ', (string) $person->name, 2);
            $card->add('N', [$parts[1] ?? '', $parts[0] ?? '', '', '', '']);
        }

        $emails = (array) ($person->emails ?? []);
        if (empty($emails) && $person->primary_email) {
            $emails = [['email' => $person->primary_email, 'type' => 'INTERNET']];
        }
        foreach ($emails as $email) {
            $value = is_array($email) ? ($email['email'] ?? $email['value'] ?? null) : $email;
            if (! $value) {
                continue;
            }
            $emailProp = $card->add('EMAIL', $value);
            $type = is_array($email) ? ($email['type'] ?? null) : null;
            if ($type) {
                $emailProp['TYPE'] = strtoupper((string) $type);
            }
            if (is_array($email) && ! empty($email['primary'])) {
                $emailProp['PREF'] = '1';
            } elseif ($value === $person->primary_email) {
                $emailProp['PREF'] = '1';
            }
        }

        $phones = (array) ($person->phone_numbers ?? []);
        if (empty($phones) && $person->primary_number) {
            $phones = [['number' => $person->primary_number, 'type' => 'CELL']];
        }
        foreach ($phones as $phone) {
            $number = is_array($phone) ? ($phone['number'] ?? $phone['value'] ?? null) : $phone;
            if (! $number) {
                continue;
            }
            $phoneProp = $card->add('TEL', $number);
            $type = is_array($phone) ? ($phone['type'] ?? null) : null;
            if ($type) {
                $phoneProp['TYPE'] = strtoupper((string) $type);
            }
            if (is_array($phone) && ! empty($phone['primary'])) {
                $phoneProp['PREF'] = '1';
            } elseif ($number === $person->primary_number) {
                $phoneProp['PREF'] = '1';
            }
        }

        $addresses = (array) ($person->addresses ?? []);
        foreach ($addresses as $address) {
            if (! is_array($address)) {
                continue;
            }
            $adrProp = $card->add('ADR', [
                (string) ($address['post_office_box'] ?? ''),
                (string) ($address['extended'] ?? ''),
                (string) ($address['street'] ?? $address['line1'] ?? ''),
                (string) ($address['city'] ?? $address['locality'] ?? ''),
                (string) ($address['region'] ?? $address['state'] ?? ''),
                (string) ($address['postal_code'] ?? $address['postcode'] ?? ''),
                (string) ($address['country'] ?? ''),
            ]);
            if (! empty($address['type'])) {
                $adrProp['TYPE'] = strtoupper((string) $address['type']);
            }
            if (! empty($address['primary'])) {
                $adrProp['PREF'] = '1';
            }
        }

        if ($person->birthdate) {
            $card->add('BDAY', $person->birthdate->format('Ymd'));
        }

        $jobs = (array) ($person->jobs ?? []);
        $primaryJob = $jobs[0] ?? null;
        if (is_array($primaryJob)) {
            if (! empty($primaryJob['organization'])) {
                $card->add('ORG', [(string) $primaryJob['organization']]);
            }
            if (! empty($primaryJob['title'])) {
                $card->add('TITLE', (string) $primaryJob['title']);
            }
        }

        if ($person->photo_url) {
            $photo = $card->add('PHOTO', $person->photo_url);
            $photo['VALUE'] = 'URI';
        }

        if ($person->pronouns) {
            $card->add('X-PRONOUNS', $person->pronouns);
        }

        $tagNames = $person->tags
            ->reject(fn ($tag) => $tag->type === self::COLLECTION_TAG_NAMESPACE)
            ->map(fn ($tag) => (string) $tag->name)
            ->all();
        if ($tagNames !== []) {
            $card->add('CATEGORIES', $tagNames);
        }

        return $card;
    }

    private function applyVCardToPerson(VCard $vcard, Person $person, string $uid): void
    {
        $identifiers = (array) ($person->identifiers ?? []);
        $identifiers[self::UID_KEY] = $uid;
        $person->identifiers = $identifiers;

        if (isset($vcard->FN)) {
            $person->name = (string) $vcard->FN;
        }

        if (isset($vcard->N)) {
            $parts = $vcard->N->getParts();
            $names = (array) ($person->names ?? []);
            $names[0] = array_filter([
                'family' => $parts[0] ?? null,
                'given' => $parts[1] ?? null,
                'additional' => $parts[2] ?? null,
                'prefix' => $parts[3] ?? null,
                'suffix' => $parts[4] ?? null,
                'nickname' => isset($vcard->NICKNAME) ? (string) $vcard->NICKNAME : null,
            ], fn ($v) => $v !== null && $v !== '');
            $person->names = $names;

            if (empty($person->name)) {
                $person->name = trim(implode(' ', array_filter([
                    $parts[1] ?? null,
                    $parts[0] ?? null,
                ])));
            }
        }

        $emails = [];
        foreach ($vcard->EMAIL ?? [] as $email) {
            $entry = [
                'email' => (string) $email,
                'type' => isset($email['TYPE']) ? strtolower((string) $email['TYPE']) : null,
                'primary' => isset($email['PREF']) && (int) (string) $email['PREF'] === 1,
            ];
            $emails[] = array_filter($entry, fn ($v) => $v !== null && $v !== '');

            if ($entry['primary'] || ! $person->primary_email) {
                $person->primary_email = (string) $email;
            }
        }
        if ($emails !== []) {
            $person->emails = $emails;
        }

        $phones = [];
        foreach ($vcard->TEL ?? [] as $phone) {
            $entry = [
                'number' => (string) $phone,
                'type' => isset($phone['TYPE']) ? strtolower((string) $phone['TYPE']) : null,
                'primary' => isset($phone['PREF']) && (int) (string) $phone['PREF'] === 1,
            ];
            $phones[] = array_filter($entry, fn ($v) => $v !== null && $v !== '');

            if ($entry['primary'] || ! $person->primary_number) {
                $person->primary_number = (string) $phone;
            }
        }
        if ($phones !== []) {
            $person->phone_numbers = $phones;
        }

        $addresses = [];
        foreach ($vcard->ADR ?? [] as $adr) {
            $parts = $adr->getParts();
            $entry = array_filter([
                'post_office_box' => $parts[0] ?? null,
                'extended' => $parts[1] ?? null,
                'street' => $parts[2] ?? null,
                'city' => $parts[3] ?? null,
                'region' => $parts[4] ?? null,
                'postal_code' => $parts[5] ?? null,
                'country' => $parts[6] ?? null,
                'type' => isset($adr['TYPE']) ? strtolower((string) $adr['TYPE']) : null,
                'primary' => isset($adr['PREF']) && (int) (string) $adr['PREF'] === 1,
            ], fn ($v) => $v !== null && $v !== '');

            $addresses[] = $entry;

            $formatted = trim(implode(', ', array_filter([
                $parts[2] ?? null,
                $parts[3] ?? null,
                $parts[4] ?? null,
                $parts[5] ?? null,
            ])));

            if ((! empty($entry['primary']) || ! $person->primary_address) && $formatted !== '') {
                $person->primary_address = $formatted;
            }
        }
        if ($addresses !== []) {
            $person->addresses = $addresses;
        }

        if (isset($vcard->BDAY)) {
            try {
                $person->birthdate = Carbon::parse((string) $vcard->BDAY);
            } catch (\Throwable) {
                // Leave birthdate alone on unparseable values.
            }
        }

        if (isset($vcard->{'X-PRONOUNS'})) {
            $person->pronouns = (string) $vcard->{'X-PRONOUNS'};
        }

        if (isset($vcard->ORG) || isset($vcard->TITLE)) {
            $jobs = (array) ($person->jobs ?? []);
            $primary = $jobs[0] ?? [];
            $orgParts = isset($vcard->ORG) ? $vcard->ORG->getParts() : [];
            if (! empty($orgParts[0])) {
                $primary['organization'] = (string) $orgParts[0];
            }
            if (isset($vcard->TITLE)) {
                $primary['title'] = (string) $vcard->TITLE;
            }
            $jobs[0] = $primary;
            $person->jobs = $jobs;
        }

        if (isset($vcard->PHOTO)) {
            $value = (string) $vcard->PHOTO;
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $person->photo_url = $value;
            }
        }
    }

    private function syncCollectionTag(Person $person, string $collection): void
    {
        $person->syncTagsWithType(
            $collection === self::DEFAULT_COLLECTION ? [] : [$collection],
            self::COLLECTION_TAG_NAMESPACE,
        );
    }
}
