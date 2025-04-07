<?php

declare(strict_types=1);

namespace App\Models\Spork;

use App\Events\Models\Script\ScriptCreated;
use App\Events\Models\Script\ScriptCreating;
use App\Events\Models\Script\ScriptDeleted;
use App\Events\Models\Script\ScriptDeleting;
use App\Events\Models\Script\ScriptUpdated;
use App\Events\Models\Script\ScriptUpdating;
use App\Models\Crud;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use App\Models\User;
use App\Observers\ApplyCredentialsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ApplyCredentialsObserver::class])]
class Script extends Model implements Crud
{
    use HasFactory;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    protected $fillable = [
        'name',
        'language',
        'script',
        'user_id',
    ];

    public $dispatchesEvents = [
        'created' => ScriptCreated::class,
        'creating' => ScriptCreating::class,
        'deleting' => ScriptDeleting::class,
        'deleted' => ScriptDeleted::class,
        'updating' => ScriptUpdating::class,
        'updated' => ScriptUpdated::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
