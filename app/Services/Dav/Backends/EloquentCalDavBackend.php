<?php

declare(strict_types=1);

namespace App\Services\Dav\Backends;

use App\Contracts\Dav\DavCollectionSource;
use App\Models\User;
use App\Services\Dav\Support\CurrentDavAuth;
use App\Services\Dav\Support\PrincipalResolver;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Plugin as CalDavPlugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;

class EloquentCalDavBackend extends AbstractBackend implements SyncSupport
{
    private const ID_GLUE = '|';

    public function __construct(
        private readonly EloquentSourceRegistry $registry,
        private readonly PrincipalResolver $principals,
        private readonly CurrentDavAuth $current,
    ) {
    }

    public function getCalendarsForUser($principalUri)
    {
        $user = $this->principals->resolveUser($principalUri);

        if (! $user) {
            return [];
        }

        $calendars = [];

        foreach ($this->registry->ofType(DavCollectionSource::TYPE_CALENDAR) as $source) {
            foreach ($source->listCollections($user) as $collection) {
                $id = $this->encodeId($source, $user, $collection['id']);
                $calendars[] = [
                    'id' => $id,
                    'uri' => $collection['uri'],
                    'principaluri' => $principalUri,
                    '{DAV:}displayname' => $collection['display_name'],
                    '{'.CalDavPlugin::NS_CALDAV.'}calendar-description' => $collection['description'],
                    '{http://calendarserver.org/ns/}getctag' => (string) $collection['ctag'],
                    '{http://sabredav.org/ns}sync-token' => (string) $collection['ctag'],
                    '{'.CalDavPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
                ];
            }
        }

        return $calendars;
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        $user = $this->principals->resolveUser($principalUri);

        if (! $user) {
            throw new Forbidden('Unknown principal');
        }

        $this->ensureCanWrite();

        $sources = $this->registry->ofType(DavCollectionSource::TYPE_CALENDAR);
        $source = $sources->first();

        if (! $source) {
            throw new Forbidden('No calendar source available');
        }

        return $this->encodeId($source, $user, $calendarUri);
    }

    public function updateCalendar($calendarId, PropPatch $propPatch)
    {
        // Tag-driven collections are read-only at the metadata level.
    }

    public function deleteCalendar($calendarId)
    {
        // No-op (see EloquentCardDavBackend::deleteAddressBook).
    }

    public function getCalendarObjects($calendarId)
    {
        [$source, $user, $collection] = $this->decode($calendarId);

        $objects = [];

        foreach ($source->listObjects($user, $collection) as $object) {
            $objects[] = [
                'id' => $object['uri'],
                'uri' => $object['uri'],
                'lastmodified' => $object['last_modified'],
                'etag' => $object['etag'],
                'size' => $object['size'],
                'calendardata' => $object['data'],
                'component' => 'vevent',
            ];
        }

        return $objects;
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        [$source, $user, $collection] = $this->decode($calendarId);

        $object = $source->findObject($user, $collection, $objectUri);

        if (! $object) {
            return null;
        }

        return [
            'id' => $object['uri'],
            'uri' => $object['uri'],
            'lastmodified' => $object['last_modified'],
            'etag' => $object['etag'],
            'size' => $object['size'],
            'calendardata' => $object['data'],
            'component' => 'vevent',
        ];
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $this->ensureCanWrite();

        [$source, $user, $collection] = $this->decode($calendarId);

        return $source->putObject($user, $collection, $objectUri, $calendarData);
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $this->ensureCanWrite();

        [$source, $user, $collection] = $this->decode($calendarId);

        return $source->putObject($user, $collection, $objectUri, $calendarData);
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
        $this->ensureCanWrite();

        [$source, $user, $collection] = $this->decode($calendarId);

        $source->deleteObject($user, $collection, $objectUri);
    }

    public function calendarQuery($calendarId, array $filters)
    {
        [$source, $user, $collection] = $this->decode($calendarId);

        $uris = [];

        foreach ($source->listObjects($user, $collection) as $object) {
            $uris[] = $object['uri'];
        }

        return $uris;
    }

    public function getCalendarObjectByUID($principalUri, $uid)
    {
        $user = $this->principals->resolveUser($principalUri);

        if (! $user) {
            return null;
        }

        foreach ($this->registry->ofType(DavCollectionSource::TYPE_CALENDAR) as $source) {
            foreach ($source->listCollections($user) as $collection) {
                $object = $source->findObject($user, $collection['id'], $uid.'.ics');
                if ($object) {
                    return $collection['id'].'/'.$object['uri'];
                }
            }
        }

        return null;
    }

    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        [$source, $user] = $this->decode($calendarId);

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
            throw new Forbidden('Malformed calendar id: '.$id);
        }

        [$sourceClass, $userId, $collection] = $parts;

        $source = $this->registry->all()->firstWhere(fn (DavCollectionSource $s) => $s::class === $sourceClass);

        if (! $source) {
            throw new Forbidden('Unknown calendar source: '.$sourceClass);
        }

        $user = User::query()->find((int) $userId);

        if (! $user) {
            throw new Forbidden('Unknown user for calendar');
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
