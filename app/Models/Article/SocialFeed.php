<?php

declare(strict_types=1);

namespace App\Models\Article;

use App\Contracts\Conditionable;
use App\Models\Crud;
use App\Models\Taggable;
use App\Models\Traits\HasConditions;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use App\Models\User;
use App\Services\Article\SocialFeedService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;

class SocialFeed extends Model implements Conditionable, Crud, Taggable
{
    use HasConditions;
    use HasFactory;
    use HasTags;
    use LogsActivity;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    public $fillable = [
        'uuid',
        'user_id',
        'name',
        'description',
        'is_public',
        'must_all_conditions_pass',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'bool',
            'must_all_conditions_pass' => 'bool',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $feed): void {
            if (empty($feed->uuid)) {
                $feed->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): \Database\Factories\Article\SocialFeedFactory
    {
        return \Database\Factories\Article\SocialFeedFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_public', false);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('user_id', $user->id)
                ->orWhere('is_public', true);
        });
    }

    public function getArticles(SocialFeedService $service): Builder
    {
        return $service->getArticlesForSocialFeed($this);
    }

    public function articlesQuery(SocialFeedService $service): Builder
    {
        return $this->getArticles($service);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['uuid', 'name', 'description', 'is_public', 'must_all_conditions_pass'])
            ->useLogName('social-feed')
            ->dontSubmitEmptyLogs()
            ->logOnlyDirty();
    }
}
