<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasProjectResource
{
    public function projects(): MorphToMany
    {
        return $this->morphToMany(
            Project::class,
            'resource',
            'project_resources'
        );
    }
}
