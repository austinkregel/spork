<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\Condition\ConditionCreated;
use App\Events\Models\Condition\ConditionCreating;
use App\Events\Models\Condition\ConditionDeleted;
use App\Events\Models\Condition\ConditionDeleting;
use App\Events\Models\Condition\ConditionUpdated;
use App\Events\Models\Condition\ConditionUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condition extends Model implements Crud
{
    use HasFactory;

    public $fillable = [
        'parameter',
        'comparator',
        'value',
        'conditionable_type',
        'conditionable_id'
    ];

    public $dispatchesEvents = [
        'created' => ConditionCreated::class,
        'creating' => ConditionCreating::class,
        'deleting' => ConditionDeleting::class,
        'deleted' => ConditionDeleted::class,
        'updating' => ConditionUpdating::class,
        'updated' => ConditionUpdated::class,
    ];
}
