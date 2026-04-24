<?php

declare(strict_types=1);

namespace App\Services\Dav\Backends;

use App\Contracts\Dav\DavCollectionSource;
use App\Models\User;
use App\Services\Dav\Sources\AbstractEloquentSource;

/**
 * Walks the activity_log to produce sync-collection diffs in the shape Sabre
 * expects. The sync-token surfaced to clients is the largest activity_log.id
 * we have already reported for that user/source pair.
 */
class SyncDiffBuilder
{
    public static function build(DavCollectionSource $source, User $user, ?string $syncToken, ?int $limit): ?array
    {
        if (! $source instanceof AbstractEloquentSource) {
            return null;
        }

        $token = self::parseToken($syncToken);

        if ($syncToken !== null && $token === null) {
            // Sabre wants null for unknown/expired tokens so it falls back to
            // an initial sync.
            return null;
        }

        $query = $source->activityQueryFor($user)->orderBy('activity_log.id');

        if ($token > 0) {
            $query->where('activity_log.id', '>', $token);
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $rows = $query->get();

        $added = $modified = $deleted = [];
        $newToken = $token;
        $modelClass = $source->modelClass();
        $instance = new $modelClass;
        $tableName = $instance->getTable();

        $modelMap = [];
        $subjectIds = $rows->pluck('subject_id')->filter()->unique()->values();
        if ($subjectIds->isNotEmpty()) {
            $models = $modelClass::query()->whereIn('id', $subjectIds)->get()->keyBy('id');
            foreach ($models as $id => $model) {
                $modelMap[$id] = $model;
            }
        }

        foreach ($rows as $row) {
            $newToken = max($newToken, (int) $row->id);

            $event = (string) $row->event;
            $subjectId = (int) $row->subject_id;

            if ($event === 'deleted') {
                $properties = self::decodeProperties($row->properties ?? null);
                $uid = self::extractUidFromProperties($source, $properties);
                if (! $uid) {
                    continue;
                }
                $deleted[] = self::uriFor($source, $uid);
                continue;
            }

            $model = $modelMap[$subjectId] ?? null;
            if (! $model) {
                continue;
            }

            $identifiers = (array) ($model->identifiers ?? []);
            $key = $source instanceof \App\Services\Dav\Sources\PersonCardDavSource
                ? \App\Services\Dav\Sources\PersonCardDavSource::UID_KEY
                : \App\Services\Dav\Sources\EventCalDavSource::UID_KEY;
            $uid = $identifiers[$key] ?? null;
            if (! $uid) {
                continue;
            }

            $uri = self::uriFor($source, $uid);

            if ($event === 'created') {
                $added[] = $uri;
            } else {
                $modified[] = $uri;
            }
        }

        return [
            'syncToken' => (string) $newToken,
            'added' => array_values(array_unique($added)),
            'modified' => array_values(array_unique($modified)),
            'deleted' => array_values(array_unique($deleted)),
        ];
    }

    private static function parseToken(?string $token): ?int
    {
        if ($token === null || $token === '') {
            return 0;
        }

        if (! ctype_digit($token)) {
            return null;
        }

        return (int) $token;
    }

    private static function uriFor(DavCollectionSource $source, string $uid): string
    {
        $extension = $source->collectionType() === DavCollectionSource::TYPE_ADDRESSBOOK ? '.vcf' : '.ics';

        return $uid.$extension;
    }

    private static function decodeProperties(mixed $properties): array
    {
        if (is_array($properties)) {
            return $properties;
        }

        if (is_string($properties)) {
            $decoded = json_decode($properties, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private static function extractUidFromProperties(DavCollectionSource $source, array $properties): ?string
    {
        $key = $source instanceof \App\Services\Dav\Sources\PersonCardDavSource
            ? \App\Services\Dav\Sources\PersonCardDavSource::UID_KEY
            : \App\Services\Dav\Sources\EventCalDavSource::UID_KEY;

        $candidates = [
            $properties['attributes']['identifiers'][$key] ?? null,
            $properties['old']['identifiers'][$key] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }
}
