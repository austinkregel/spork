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
trait ScopeRelativeSearch
{
    public function scopeRelative(Builder $query, string $string): void
    {
        switch ($string) {
            case 'user':
                if (auth()->check()) {
                    $query->where('user_id', auth()->id());
                }

                break;
            default:
                abort(404, 'Not Found');
        }
    }
}
