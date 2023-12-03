<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;

class ExternalRssFeed extends Model implements Crud
{
    use HasFactory, HasTags, LogsActivity;

    public $fillable = [
        'uuid',
        'url',
        'name',
        'profile_photo_path',
    ];

    public function articles()
    {
        return $this->morphMany(Article::class, 'author');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['uuid', 'url', 'name', 'profile_photo_path'])
            ->useLogName('external-rss-feed')
            ->logOnlyDirty();
    }
}
