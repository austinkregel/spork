<?php

declare(strict_types=1);

namespace App\Services\Dav\Sources;

use App\Contracts\Dav\DavCollectionSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\HasTags;

/**
 * Shared scaffolding for all Eloquent-backed DAV collection sources. Handles
 * tag-driven collection naming, etag generation and per-user scoping.
 */
abstract class AbstractEloquentSource implements DavCollectionSource
{
    public function principalUri(User $user): string
    {
        return 'principals/'.$user->getKey();
    }

    public function listCollections(User $user): array
    {
        $collections = [
            self::DEFAULT_COLLECTION => [
                'id' => self::DEFAULT_COLLECTION,
                'uri' => self::DEFAULT_COLLECTION,
                'display_name' => $this->defaultDisplayName(),
                'description' => $this->defaultDescription(),
                'ctag' => $this->ctagFor($user, self::DEFAULT_COLLECTION),
            ],
        ];

        foreach ($this->collectionTagSlugs($user) as $slug) {
            if ($slug === self::DEFAULT_COLLECTION) {
                continue;
            }

            $collections[$slug] = [
                'id' => $slug,
                'uri' => $slug,
                'display_name' => ucfirst(str_replace('-', ' ', $slug)),
                'description' => $this->defaultDescription().' ('.$slug.')',
                'ctag' => $this->ctagFor($user, $slug),
            ];
        }

        return array_values($collections);
    }

    public function listObjects(User $user, string $collection): iterable
    {
        return $this->collectionQuery($user, $collection)
            ->get()
            ->map(fn (Model $model) => $this->serializeModel($model))
            ->all();
    }

    public function findObject(User $user, string $collection, string $uri): ?array
    {
        $model = $this->findModel($user, $collection, $uri);

        return $model ? $this->serializeModel($model) : null;
    }

    public function findModel(User $user, string $collection, string $uri): ?Model
    {
        $identifier = $this->stripUriExtension($uri);

        return $this->collectionQuery($user, $collection)
            ->where($this->uidColumn().'->'.$this->uidJsonKey(), $identifier)
            ->first();
    }

    public function deleteObject(User $user, string $collection, string $uri): void
    {
        $model = $this->findModel($user, $collection, $uri);

        if ($model) {
            $model->delete();
        }
    }

    /**
     * Returns the largest activity_log id touching this source for $user.
     * Used both for collection ctags and as a fallback initial sync-token.
     *
     * We join through the subject table so we capture changes regardless of
     * whether they were caused by the user or by a background process. For
     * deletes we fall back to the causer_id since the row is gone.
     */
    public function maxActivityIdFor(User $user): int
    {
        return (int) $this->activityQueryFor($user)->max('activity_log.id');
    }

    public function activityQueryFor(User $user): \Illuminate\Database\Query\Builder
    {
        $modelClass = $this->modelClass();
        $tableName = (new $modelClass)->getTable();

        return DB::table('activity_log')
            ->where('activity_log.subject_type', $modelClass)
            ->where(function ($query) use ($tableName, $user) {
                $query->whereExists(function ($exists) use ($tableName, $user) {
                    $exists->select(DB::raw(1))
                        ->from($tableName)
                        ->whereColumn($tableName.'.id', 'activity_log.subject_id')
                        ->where($tableName.'.user_id', $user->getKey());
                })->orWhere(function ($q) use ($user) {
                    $q->where('activity_log.event', 'deleted')
                        ->where('activity_log.causer_id', $user->getKey())
                        ->where('activity_log.causer_type', User::class);
                });
            });
    }

    protected function ctagFor(User $user, string $collection): string
    {
        return (string) $this->maxActivityIdFor($user);
    }

    protected function collectionTagSlugs(User $user): array
    {
        $modelClass = $this->modelClass();

        if (! in_array(HasTags::class, class_uses_recursive($modelClass), true)) {
            return [];
        }

        $tableName = (new $modelClass)->getTable();

        $rows = DB::table('tags')
            ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
            ->join($tableName, function ($join) use ($modelClass, $tableName) {
                $join->on($tableName.'.id', '=', 'taggables.taggable_id')
                    ->where('taggables.taggable_type', '=', $modelClass);
            })
            ->where($tableName.'.user_id', $user->getKey())
            ->where('tags.type', self::COLLECTION_TAG_NAMESPACE)
            ->distinct()
            ->pluck('tags.name');

        return $rows->map(function ($name) {
            // Tag names are stored as JSON for translatable compatibility.
            $decoded = json_decode((string) $name, true);

            if (is_array($decoded)) {
                return (string) array_values($decoded)[0];
            }

            return (string) $name;
        })->all();
    }

    protected function collectionQuery(User $user, string $collection): Builder
    {
        $modelClass = $this->modelClass();
        $query = $modelClass::query()->where('user_id', $user->getKey());

        if ($collection === self::DEFAULT_COLLECTION) {
            return $query->whereDoesntHave('tags', function ($q) {
                $q->where('type', self::COLLECTION_TAG_NAMESPACE);
            });
        }

        return $query->whereHas('tags', function ($q) use ($collection) {
            $q->where('type', self::COLLECTION_TAG_NAMESPACE)
                ->where('name->en', $collection);
        });
    }

    protected function etagFor(Model $model): string
    {
        $stamp = $model->updated_at?->getTimestamp() ?? 0;

        return '"'.sha1($model->getKey().'|'.$stamp).'"';
    }

    protected function serializeModel(Model $model): array
    {
        $body = $this->renderObjectBody($model);
        $uri = $this->objectUriFor($model);

        return [
            'uri' => $uri,
            'etag' => $this->etagFor($model),
            'last_modified' => $model->updated_at?->getTimestamp() ?? time(),
            'size' => strlen($body),
            'data' => $body,
        ];
    }

    abstract protected function defaultDisplayName(): string;

    abstract protected function defaultDescription(): string;

    abstract protected function uidColumn(): string;

    abstract protected function uidJsonKey(): string;

    abstract protected function objectUriFor(Model $model): string;

    abstract protected function renderObjectBody(Model $model): string;

    abstract protected function stripUriExtension(string $uri): string;
}
