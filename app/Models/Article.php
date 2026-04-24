<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Events\Models\Article\ArticleCreated;
use App\Events\Models\Article\ArticleCreating;
use App\Events\Models\Article\ArticleDeleted;
use App\Events\Models\Article\ArticleDeleting;
use App\Events\Models\Article\ArticleUpdated;
use App\Events\Models\Article\ArticleUpdating;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use App\Services\News\Feeds\FeedItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;

class Article extends Model implements ModelQuery, Taggable
{
    use HasFactory;
    use HasTags;
    use LogsActivity;
    use ScopeQSearch;
    use ScopeRelativeSearch;
    use Searchable;

    public $fillable = [
        'uuid',
        'external_guid',
        'author_type',
        'author_id',
        'last_modified',
        'etag',
        'headline',
        'content',
        'attachment',
        'url',
    ];

    public $dispatchesEvents = [
        'created' => ArticleCreated::class,
        'creating' => ArticleCreating::class,
        'deleting' => ArticleDeleting::class,
        'deleted' => ArticleDeleted::class,
        'updating' => ArticleUpdating::class,
        'updated' => ArticleUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'last_modified' => 'datetime',
        ];
    }

    public static function fromFeedItem(ExternalRssFeed $feed, FeedItem $item): self
    {
        // Calculate the external_guid value that will be used for the unique constraint
        $external_guid = $item->getUuidIfExists() ?? $item->getUrl();

        // Use updateOrCreate to handle duplicates gracefully and prevent race conditions
        return self::updateOrCreate(
            ['external_guid' => $external_guid],
            [
                // If the item's GUID is a v4 UUID, we may as well use it as our UUID.
                'uuid' => $item->getUuidIfExists(),
                'author_id' => $feed->id,
                'author_type' => get_class($feed),
                'headline' => $item->getTitle(),
                'content' => $item->getContent(),
                'attachment' => $item->getUrl(),
                'url' => $item->getUrl(),
                'created_at' => $item->getPublishedAt(),
                'last_modified' => $item->getPublishedAt(),
            ]
        );
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function externalRssFeed(): BelongsTo
    {
        return $this->belongsTo(ExternalRssFeed::class, 'author_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['headline', 'content', 'attachment', 'url'])
            ->useLogName('article')
            ->logFillable()
            ->dontSubmitEmptyLogs()
            ->logOnlyDirty();
    }
}
