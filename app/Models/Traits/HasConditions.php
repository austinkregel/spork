<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Article;
use App\Models\Condition;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @extends \Eloquent
 */
trait HasConditions
{
    public function conditions(): MorphMany
    {
        return $this->morphMany(Condition::class, 'conditionable')->orderByDesc('created_at');
    }
}
