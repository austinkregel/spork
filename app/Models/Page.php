<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Events\Models\Page\PageCreated;
use App\Events\Models\Page\PageCreating;
use App\Events\Models\Page\PageDeleted;
use App\Events\Models\Page\PageDeleting;
use App\Events\Models\Page\PageUpdated;
use App\Events\Models\Page\PageUpdating;
use App\Models\Traits\HasProjectResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model implements Crud, ModelQuery
{
    use HasFactory;
    use HasProjectResource;

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

    public $dispatchesEvents = [
        'created' => PageCreated::class,
        'creating' => PageCreating::class,
        'deleting' => PageDeleting::class,
        'deleted' => PageDeleted::class,
        'updating' => PageUpdating::class,
        'updated' => PageUpdated::class,
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
