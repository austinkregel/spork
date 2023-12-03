<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Models\Traits\HasProjectResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model implements ModelQuery, Crud
{
    use HasFactory, HasProjectResource;

    public $fillable = [
        'title',
        'uri',
        'slug',
        'route',
        'middleware',
        'subtitle',
        'excerpt',
        'content',
        'view',
        'redirect',
        'is_active',
        'sort_order',
        'published_at',
    ];

    public $casts = [
        'is_active' => 'boolean',
        'middleware' => 'json',
        'settings' => 'json',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
