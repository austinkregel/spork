<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \Eloquent
 */
trait ScopeQSearch
{
    #[Scope]
    protected function q(Builder $query, string $string): void
    {
        $query->where('name', 'like', '%'.$string.'%');
    }
}
