<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\ExternalRssFeed\ExternalRssFeedCreated;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedCreating;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedDeleted;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedDeleting;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedUpdated;
use App\Events\Models\ExternalRssFeed\ExternalRssFeedUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;

class ExternalRssFeed extends Model implements Crud, Taggable
{
    use HasFactory;
    use HasTags;
    use LogsActivity;

    public $fillable = [
        'uuid',
        'url',
        'name',
        'profile_photo_path',
        'owner_id',
        'owner_type',
    ];

    public $dispatchesEvents = [
        'created' => ExternalRssFeedCreated::class,
        'creating' => ExternalRssFeedCreating::class,
        'deleting' => ExternalRssFeedDeleting::class,
        'deleted' => ExternalRssFeedDeleted::class,
        'updating' => ExternalRssFeedUpdating::class,
        'updated' => ExternalRssFeedUpdated::class,
    ];

    public function articles()
    {
        return $this->morphMany(Article::class, 'author');
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['uuid', 'url', 'name', 'profile_photo_path'])
            ->useLogName('external-rss-feed')
            ->logOnlyDirty();
    }
}
