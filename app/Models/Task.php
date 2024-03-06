<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Events\Models\Task\TaskCreated;
use App\Events\Models\Task\TaskCreating;
use App\Events\Models\Task\TaskDeleted;
use App\Events\Models\Task\TaskDeleting;
use App\Events\Models\Task\TaskUpdated;
use App\Events\Models\Task\TaskUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model implements Crud, ModelQuery
{
    use HasFactory;

    public $fillable = [
        'name',
        'type',
        'status',
        'checklist',
        'notes',
        'start_date',
        'end_date',
        'service_identifier',
    ];

    protected $casts = [
        'checklist' => 'json',
    ];

    public $dispatchesEvents = [
        'created' => TaskCreated::class,
        'creating' =>TaskCreating::class,
        'deleting' =>TaskDeleting::class,
        'deleted' => TaskDeleted::class,
        'updating' =>TaskUpdating::class,
        'updated' => TaskUpdated::class,
    ];


    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
