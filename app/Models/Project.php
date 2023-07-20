<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Services\SshKeyGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Tags\HasTags;

class Project extends Model implements ModelQuery
{
    use HasFactory, HasTags;

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

    public function credentials(): MorphToMany
    {
        return $this->morphedByMany(
            Credential::class,
            'resource',
            'project_resources'
        );
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function credentialFor(string $service): ?Credential
    {
        $credential = $this->credentials()->where('service', $service)->first();

        if (! $credential) {
            if (Credential::TYPE_SSH === $service) {
                $generatorService = new SshKeyGeneratorService();

                $credential = $this->credentials()->create([
                    'service' => Credential::TYPE_SSH,
                    'type' => Credential::TYPE_SSH,
                    'name' => 'Forge',
                    'pub_key' => encrypt($generatorService->getPublicKey()),
                    'private_key' => encrypt($generatorService->getPrivateKey()),
                    'user_id' => auth()->id(),
                ]);

                return $credential;
            }

            throw new \Exception('No credential found for '.$service);
        }

        return $credential;
    }
}
