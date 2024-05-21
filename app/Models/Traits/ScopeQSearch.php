<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \Eloquent
 */
trait ScopeQSearch
{
    public function scopeQ(Builder $query, string $string): void
    {
        $query->where('name', 'like', '%'.$string.'%');
    }
}
