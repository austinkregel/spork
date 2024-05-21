<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Article;
use App\Models\Crud;
use App\Models\Project;
use App\Models\User;
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
                    if (method_exists($this, 'credentials') && get_class($this) !== Project::class) {
                        $query->whereHas('credentials', function (Builder $query) {
                            $query->where('user_id', auth()->id());
                        });
                    } elseif (method_exists($this, 'credential')) {
                        $query->whereHas('credential', function (Builder $query) {
                            $query->where('user_id', auth()->id());
                        });
                    } elseif (method_exists($this, 'users')) {
                        $query->whereHas('users', function (Builder $query) {
                            $query->where('id', auth()->id());
                        });
                    } elseif (method_exists($this, 'user')) {
                        $query->where('user_id', auth()->id());
                    } elseif (method_exists($this, 'team')) {
                        $query->whereHas('team', function (Builder $query) {
                            $query->where('user_id', auth()->id());
                        });
                    } elseif (method_exists($this, 'owner')) {
                        $query->where('owner_id', auth()->id())
                            ->where('owner_type', User::class);
                    } else {
                        abort(400, 'No user relation found for model');
                    }
                }

                break;
            default:
                abort(404, 'Not Found');
        }
    }
}
