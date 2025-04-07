<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Deployment extends Model
{
    public $table = 'deployment';

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'primary_domain_id', 'id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'primary_server_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function domains(): MorphToMany
    {
        return $this->morphedByMany(Domain::class, 'resource', 'deployment_resources')->withPivot('settings');
    }

    public function servers(): MorphToMany
    {
        return $this->morphedByMany(Server::class, 'resource', 'deployment_resources');
    }
}
