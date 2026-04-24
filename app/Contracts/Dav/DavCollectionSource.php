<?php

declare(strict_types=1);

namespace App\Contracts\Dav;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A DavCollectionSource maps a single Eloquent model to one or more DAV
 * collections (an addressbook for CardDAV or a calendar for CalDAV).
 *
 * Implementations are auto-discovered via App\Services\Code so adding a new
 * exposed model only requires implementing this contract.
 */
interface DavCollectionSource
{
    public const TYPE_ADDRESSBOOK = 'addressbook';
    public const TYPE_CALENDAR = 'calendar';

    public const COLLECTION_TAG_NAMESPACE = 'dav-collection';

    public const DEFAULT_COLLECTION = 'default';

    /**
     * The Eloquent model class this source handles. Used to filter
     * activity_log rows when building sync-tokens.
     */
    public function modelClass(): string;

    /**
     * Either DavCollectionSource::TYPE_ADDRESSBOOK or TYPE_CALENDAR.
     */
    public function collectionType(): string;

    /**
     * URI used by the principal collection (e.g. "principals/42").
     */
    public function principalUri(User $user): string;

    /**
     * The list of collections the given user has under this source.
     * Each item must include keys: id, uri, display_name, description, ctag.
     * `id` is what gets passed back to the other methods to identify the
     * collection (we use "default" + any tag in the dav-collection namespace).
     *
     * @return array<int, array{id: string, uri: string, display_name: string, description: string, ctag: string}>
     */
    public function listCollections(User $user): array;

    /**
     * Return all object cards (vCards / iCals) for the given collection.
     * Each object must include keys: uri, etag, last_modified, size, data.
     *
     * @return iterable<int, array{uri: string, etag: string, last_modified: int, size: int, data: string}>
     */
    public function listObjects(User $user, string $collection): iterable;

    /**
     * Look up a single object by its URI inside a collection. Returns null
     * if not found; otherwise the same shape as listObjects entries.
     *
     * @return array{uri: string, etag: string, last_modified: int, size: int, data: string}|null
     */
    public function findObject(User $user, string $collection, string $uri): ?array;

    /**
     * Persist a vCard/iCal coming from a DAV client. Returns the new etag.
     */
    public function putObject(User $user, string $collection, string $uri, string $body): string;

    /**
     * Remove an object by URI. No-op if it doesn't exist.
     */
    public function deleteObject(User $user, string $collection, string $uri): void;

    /**
     * Resolve the underlying Eloquent model for an object URI inside a
     * collection. Used by the backends when they need to read raw model
     * state (mostly for sync diffs).
     */
    public function findModel(User $user, string $collection, string $uri): ?Model;
}
