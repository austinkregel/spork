<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Deployment extends Model
{
    use HasFactory;
    public $table = 'deployment';
    protected $fillable = [
        'project_id',
        'name',
        'type',
        'primary_domain_id',
        'primary_server_id',
        'deployment_strategies',
        'repository_base_url',
        'repository_status',
        'user',
        'maintenance_mode',
        'full_path',
        'executable_version',
        'executable_binary',
        'secured',
        'deploy_started_at',
        'deploy_ended_at',
        'last_deployed_at',
        'last_deployed_commit',
        'deployment_duration',
    ];
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
    public function credentials(): MorphToMany
    {
        return $this->morphedByMany(Credential::class, 'resource', 'deployment_resources');
    }
}
