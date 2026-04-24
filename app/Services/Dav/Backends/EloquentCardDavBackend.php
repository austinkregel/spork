<?php

declare(strict_types=1);

namespace App\Services\Dav\Backends;

use App\Contracts\Dav\DavCollectionSource;
use App\Models\User;
use App\Services\Dav\Support\CurrentDavAuth;
use App\Services\Dav\Support\PrincipalResolver;
use Sabre\CardDAV\Backend\AbstractBackend;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\CardDAV\Plugin as CardDavPlugin;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;

/**
 * Generic CardDAV backend that proxies all reads and writes to whichever
 * DavCollectionSource owns a given address book id. Address book ids are
 * encoded as "{sourceClass}|{userId}|{collectionSlug}".
 */
class EloquentCardDavBackend extends AbstractBackend implements SyncSupport
{
    private const ID_GLUE = '|';

    public function __construct(
        private readonly EloquentSourceRegistry $registry,
        private readonly PrincipalResolver $principals,
        private readonly CurrentDavAuth $current,
    ) {
    }

    public function getAddressBooksForUser($principalUri)
    {
        $user = $this->principals->resolveUser($principalUri);

        if (! $user) {
            return [];
        }

        $books = [];

        foreach ($this->registry->ofType(DavCollectionSource::TYPE_ADDRESSBOOK) as $source) {
            foreach ($source->listCollections($user) as $collection) {
                $id = $this->encodeId($source, $user, $collection['id']);
                $books[] = [
                    'id' => $id,
                    'uri' => $collection['uri'],
                    'principaluri' => $principalUri,
                    '{DAV:}displayname' => $collection['display_name'],
                    '{'.CardDavPlugin::NS_CARDDAV.'}addressbook-description' => $collection['description'],
                    '{http://calendarserver.org/ns/}getctag' => (string) $collection['ctag'],
                    '{http://sabredav.org/ns}sync-token' => (string) $collection['ctag'],
                ];
            }
        }

        return $books;
    }

    public function updateAddressBook($addressBookId, PropPatch $propPatch)
    {
        // Tag-driven collections are read-only at the metadata level. Property
        // mutations are silently ignored.
    }

    public function createAddressBook($principalUri, $url, array $properties)
    {
        $user = $this->principals->resolveUser($principalUri);

        if (! $user) {
            throw new Forbidden('Unknown principal');
        }

        $this->ensureCanWrite();

        $sources = $this->registry->ofType(DavCollectionSource::TYPE_ADDRESSBOOK);
        $source = $sources->first();

        if (! $source) {
            throw new Forbidden('No addressbook source available');
        }

        // Creating an address book translates into making the collection
        // discoverable; nothing to persist beyond that since the slug only
        // surfaces once a Person is tagged with dav-collection:{slug}.
        return $this->encodeId($source, $user, $url);
    }

    public function deleteAddressBook($addressBookId)
    {
        // No-op: tag-driven collections cannot be deleted independently of
        // their members. Untagging contacts removes the collection itself.
    }

    public function getCards($addressbookId)
    {
        [$source, $user, $collection] = $this->decode($addressbookId);

        $cards = [];

        foreach ($source->listObjects($user, $collection) as $object) {
            $cards[] = [
                'id' => $object['uri'],
                'uri' => $object['uri'],
                'lastmodified' => $object['last_modified'],
                'etag' => $object['etag'],
                'size' => $object['size'],
                'carddata' => $object['data'],
            ];
        }

        return $cards;
    }

    public function getCard($addressBookId, $cardUri)
    {
        [$source, $user, $collection] = $this->decode($addressBookId);

        $object = $source->findObject($user, $collection, $cardUri);

        if (! $object) {
            return false;
        }

        return [
            'id' => $object['uri'],
            'uri' => $object['uri'],
            'lastmodified' => $object['last_modified'],
            'etag' => $object['etag'],
            'size' => $object['size'],
            'carddata' => $object['data'],
        ];
    }

    public function createCard($addressBookId, $cardUri, $cardData)
    {
        $this->ensureCanWrite();

        [$source, $user, $collection] = $this->decode($addressBookId);

        return $source->putObject($user, $collection, $cardUri, $cardData);
    }

    public function updateCard($addressBookId, $cardUri, $cardData)
    {
        $this->ensureCanWrite();

        [$source, $user, $collection] = $this->decode($addressBookId);

        return $source->putObject($user, $collection, $cardUri, $cardData);
    }

    public function deleteCard($addressBookId, $cardUri)
    {
        $this->ensureCanWrite();

        [$source, $user, $collection] = $this->decode($addressBookId);

        $source->deleteObject($user, $collection, $cardUri);

        return true;
    }

    public function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null)
    {
        [$source, $user] = $this->decode($addressBookId);

        return SyncDiffBuilder::build($source, $user, $syncToken, $limit);
    }

    private function encodeId(DavCollectionSource $source, User $user, string $collection): string
    {
        return implode(self::ID_GLUE, [$source::class, $user->getKey(), $collection]);
    }

    /**
     * @return array{0: DavCollectionSource, 1: User, 2: string}
     */
    private function decode(string $id): array
    {
        $parts = explode(self::ID_GLUE, $id, 3);

        if (count($parts) !== 3) {
            throw new Forbidden('Malformed address book id: '.$id);
        }

        [$sourceClass, $userId, $collection] = $parts;

        $source = $this->registry->all()->firstWhere(fn (DavCollectionSource $s) => $s::class === $sourceClass);

        if (! $source) {
            throw new Forbidden('Unknown address book source: '.$sourceClass);
        }

        $user = User::query()->find((int) $userId);

        if (! $user) {
            throw new Forbidden('Unknown user for address book');
        }

        return [$source, $user, $collection];
    }

    private function ensureCanWrite(): void
    {
        if ($this->current->user() === null) {
            throw new Forbidden('Authentication required');
        }

        if (! $this->current->can(SanctumTokenAuthBackend::ABILITY_WRITE)) {
            throw new Forbidden('Token missing dav:write ability');
        }
    }
}
