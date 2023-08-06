<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends \Spatie\Tags\Tag implements ModelQuery
{
    use HasFactory;

    public $guarded = [];

    public function articles()
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }

    public function feeds()
    {
        return $this->morphedByMany(ExternalRssFeed::class, 'taggable');
    }
}
