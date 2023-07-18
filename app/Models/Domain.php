<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Models\Traits\HasProjectResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model implements ModelQuery
{
    use HasFactory,  HasProjectResource;

    public $fillable = [
        'name',
        'verification_key',
        'cloudflare_id',
        'domain_id',
        'registered_at',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(DomainRecord::class);
    }

    public function domainAnalytics(): HasMany
    {
        return $this->hasMany(DomainAnalytics::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
