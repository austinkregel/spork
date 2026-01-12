<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Condition;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @extends \Eloquent
 */
trait HasConditions
{
    public function conditions(): MorphMany
    {
        // Deterministic ordering is important for stable condition evaluation and tests.
        // Use ascending primary key order which reflects creation order.
        return $this->morphMany(Condition::class, 'conditionable')->orderBy('id');
    }
}
