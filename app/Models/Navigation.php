<?php

namespace App\Models;

use App\Events\Models\Navigation\NavigationCreated;
use App\Events\Models\Navigation\NavigationCreating;
use App\Events\Models\Navigation\NavigationDeleted;
use App\Events\Models\Navigation\NavigationDeleting;
use App\Events\Models\Navigation\NavigationUpdated;
use App\Events\Models\Navigation\NavigationUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model implements Crud
{
    use HasFactory;

    public $guarded = [];

    public $casts = [
        'authentication_required' => 'boolean',
        'settings' => 'json'
    ];

    public $dispatchesEvents = [
        'created' => NavigationCreated::class,
        'creating' => NavigationCreating::class,
        'deleting' => NavigationDeleting::class,
        'deleted' => NavigationDeleted::class,
        'updating' => NavigationUpdating::class,
        'updated' => NavigationUpdated::class,
    ];

    public function children()
    {
        return $this->hasMany(Navigation::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Navigation::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
