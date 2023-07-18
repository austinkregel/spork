<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
