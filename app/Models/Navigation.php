<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Contracts\Conditionable;
use App\Events\Models\Navigation\NavigationCreated;
use App\Events\Models\Navigation\NavigationCreating;
use App\Events\Models\Navigation\NavigationDeleted;
use App\Events\Models\Navigation\NavigationDeleting;
use App\Events\Models\Navigation\NavigationUpdated;
use App\Events\Models\Navigation\NavigationUpdating;
use App\Models\Traits\HasConditions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model implements Conditionable, Crud
{
    use HasConditions, HasFactory;

    public $guarded = [];

    public $hidden = [
        'id', 'created_at', 'updated_at',
    ];

    public $dispatchesEvents = [
        'created' => NavigationCreated::class,
        'creating' => NavigationCreating::class,
        'deleting' => NavigationDeleting::class,
        'deleted' => NavigationDeleted::class,
        'updating' => NavigationUpdating::class,
        'updated' => NavigationUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'authentication_required' => 'boolean',
            'settings' => 'json',
        ];
    }

    public function children(): HasMany
    {
        return $this->hasMany(Navigation::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Navigation::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
