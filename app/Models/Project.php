<?php

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Project extends Model  implements  ModelQuery
{
    use HasFactory;

    public $guarded = [];

    protected $casts = ['settings' => 'json'];

    public function scopeQ(Builder $query, string $string): void
    {
         $query->where('name', 'like', '%'.$string.'%');
    }


    public function domains(): MorphToMany
    {
        return $this->morphedByMany(
            Domain::class,
            'resource',
            'project_resources'
        );
    }
    public function servers(): MorphToMany
    {
        return $this->morphedByMany(
            Server::class,
            'resource',
            'project_resources'
        );
    }

    public function research(): MorphToMany
    {
        return $this->morphedByMany(
            Research::class,
            'resource',
            'project_resources'
        );
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(
            Page::class,
            'resource',
            'project_resources'
        );
    }
}
