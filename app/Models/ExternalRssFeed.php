<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExternalRssFeed extends Model
{
    use HasFactory;
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
}
