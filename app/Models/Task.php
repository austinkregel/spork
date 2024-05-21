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
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model implements Crud, ModelQuery
{
    use HasFactory;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    public $fillable = ['name', 'type', 'status', 'checklist', 'notes', 'start_date', 'end_date', 'service_identifier'];

    public $dispatchesEvents = [
        'created' => TaskCreated::class,
        'creating' => TaskCreating::class,
        'deleting' => TaskDeleting::class,
        'deleted' => TaskDeleted::class,
        'updating' => TaskUpdating::class,
        'updated' => TaskUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'json',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
