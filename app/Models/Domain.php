<?php

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Domain extends Model implements ModelQuery
{
    use HasFactory;

    public $guarded = [];

    public function records(): HasMany
    {
        return $this->hasMany(DomainRecord::class);
    }

    public function domainAnalytics(): HasMany
    {
        return $this->hasMany(DomainAnalytics::class);
    }

    public function projects(): MorphToMany
    {
        return $this->morphToMany(
            Project::class,
            'resource',
            'project_resources'
        );
    }
}
