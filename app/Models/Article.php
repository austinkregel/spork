<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\News\Feeds\FeedItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Article extends Model implements Crud
{
    use HasFactory, LogsActivity;

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

    public $casts = [
        'last_modified' => 'datetime',
    ];

    public static function fromFeedItem(ExternalRssFeed $feed, FeedItem $item): self
    {
        if ($post = self::firstWhere('external_guid', $item->getExternalId())) {
            return $post;
        }

        $post = new Article();
        // If the item's GUID is a v4 UUID, we may as well use it as our UUID.
        $post->uuid = $item->getUuidIfExists();
        $post->external_guid = $item->getExternalId();
        $post->author_id = $feed->id;
        $post->author_type = get_class($feed);
        $post->headline = $item->getTitle();
        $post->content = $item->getContent();
        $post->attachment = $item->getUrl();
        $post->url = $item->getUrl();
        $post->created_at = $item->getPublishedAt();
        $post->last_modified = $item->getPublishedAt();
        $post->save();

        return $post;
    }

    public function author()
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['headline', 'content', 'attachment', 'url'])
            ->useLogName('article')
            ->logFillable()
            ->logOnlyDirty();
    }
}
