<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Events\Models\Budget\BudgetCreated;
use App\Events\Models\Budget\BudgetCreating;
use App\Events\Models\Budget\BudgetDeleted;
use App\Events\Models\Budget\BudgetDeleting;
use App\Events\Models\Budget\BudgetUpdated;
use App\Events\Models\Budget\BudgetUpdating;
use App\Models\Crud;
use App\Models\Taggable;
use App\Models\Traits\HasProjectResource;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use App\Models\User;
use App\Navigation\Pillar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Tags\HasTags;

class Budget extends Model implements Crud, Taggable
{
    use HasFactory;
    use HasProjectResource;
    use HasTags;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    protected $fillable = [
        'name',
        'amount',
        'user_id',
        'started_at',
        'frequency',
        'interval',
        'count',
        'breached_at',
    ];

    /**
     * Supported budget frequencies treated as RFC 5545-like FREQ values.
     */
    public const FREQUENCY_DAILY = 'DAILY';

    public const FREQUENCY_WEEKLY = 'WEEKLY';

    public const FREQUENCY_BIWEEKLY = 'BIWEEKLY';

    public const FREQUENCY_SEMIMONTHLY = 'SEMIMONTHLY';

    public const FREQUENCY_MONTHLY = 'MONTHLY';

    public const FREQUENCY_BIMONTHLY = 'BIMONTHLY';

    public const FREQUENCY_YEARLY = 'YEARLY';

    public $dispatchesEvents = [
        'created' => BudgetCreated::class,
        'creating' => BudgetCreating::class,
        'deleting' => BudgetDeleting::class,
        'deleted' => BudgetDeleted::class,
        'updating' => BudgetUpdating::class,
        'updated' => BudgetUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'breached_at' => 'datetime',
            'amount' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Normalized frequency string (uppercased) or null.
     */
    public function getFrequencyEnum(): ?string
    {
        $frequency = $this->frequency;

        return $frequency !== null ? strtoupper($frequency) : null;
    }

    /**
     * Normalized interval as positive integer, defaults to 1.
     */
    public function getIntervalInt(): int
    {
        $interval = (int) ($this->interval ?? 1);

        return $interval > 0 ? $interval : 1;
    }

    /**
     * Whether this budget has a finite number of recurrences.
     */
    public function isFinite(): bool
    {
        return $this->count !== null;
    }

    /**
     * Opt-in: surface this model under Finance pillar → Manage in the glass sub-nav.
     */
    public static function pillar(): ?Pillar
    {
        return Pillar::FINANCE;
    }
}
