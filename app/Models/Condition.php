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
    public const ALL_COMPARATORS = [
        self::COMPARATOR_EQUALS,
        self::COMPARATOR_LIKE_STRICT,
        self::COMPARATOR_NOT_EQUAL,
        self::COMPARATOR_LIKE,
        self::COMPARATOR_NOT_LIKE,
        self::COMPARATOR_IN,
        self::COMPARATOR_NOT_IN,
        self::COMPARATOR_STARTS_WITH,
        self::COMPARATOR_ENDS_WITH,
        self::COMPARATOR_LESS_THAN,
        self::COMPARATOR_LESS_THAN_EQUAL,
        self::COMPARATOR_GREATER_THAN,
        self::COMPARATOR_GREATER_THAN_EQUAL,
    ];

    public const COMPARATOR_EQUALS = 'EQUALS';
    public const COMPARATOR_NOT_EQUAL = 'NOT_EQUAL';
    public const COMPARATOR_LIKE = 'LIKE';
    public const COMPARATOR_LIKE_STRICT = 'LIKE_STRICT';
    public const COMPARATOR_NOT_LIKE = 'NOTLIKE';
    public const COMPARATOR_IN = 'IN';
    public const COMPARATOR_NOT_IN = 'NOTIN';
    public const COMPARATOR_STARTS_WITH = 'STARTS_WITH';
    public const COMPARATOR_ENDS_WITH = 'ENDS_WITH';
    public const COMPARATOR_LESS_THAN = 'LESS_THAN';
    public const COMPARATOR_LESS_THAN_EQUAL = 'LESS_THAN_EQUAL';
    public const COMPARATOR_GREATER_THAN = 'GREATER_THAN';
    public const COMPARATOR_GREATER_THAN_EQUAL = 'GREATER_THAN_EQUAL';

    public $fillable = [
        'parameter',
        'comparator',
        'value',
        'conditionable_type',
        'conditionable_id',
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
