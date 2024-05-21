<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Article;
use App\Models\Crud;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
