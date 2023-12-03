<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;

class Server extends Model implements ModelQuery, Crud
{
    use HasFactory, HasTags, LogsActivity;

    public $fillable = [
        'server_id',
        'name',
        'vcpu',
        'memory',
        'disk',
        'cost_per_hour',
        'ip_address',
        'ip_address_v6',
        'internal_ip_address',
        'internal_ip_address_v6',
        'internal_url',
        'last_ping_at',
        'booted_at',
        'turned_off_at',
        'os',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function projects(): MorphToMany
    {
        return $this->morphToMany(
            Project::class,
            'resource',
            'project_resources'
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'vcpu', 'memory', 'disk', 'cost_per_hour', 'internal_url', 'last_ping_at', 'booted_at', 'turned_off_at', 'os'])
            ->useLogName('server')
            ->logOnlyDirty();
    }
}
