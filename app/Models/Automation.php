<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Tags\HasTags;

class Automation extends Model implements Crud, ModelQuery, Taggable
{
    use HasFactory;
    use HasTags;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'enabled',
        'cron_expression',
        'timezone',
        'pacing_per_host_ms',
        'max_concurrency',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'bool',
            'pacing_per_host_ms' => 'int',
            'max_concurrency' => 'int',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $automation): void {
            if (empty($automation->slug) && ! empty($automation->name)) {
                $automation->slug = Str::slug((string) $automation->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AutomationStep::class)->orderBy('order');
    }
}


