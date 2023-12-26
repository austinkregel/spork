<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\DomainAnalytics\DomainAnalyticsCreated;
use App\Events\Models\DomainAnalytics\DomainAnalyticsCreating;
use App\Events\Models\DomainAnalytics\DomainAnalyticsDeleted;
use App\Events\Models\DomainAnalytics\DomainAnalyticsDeleting;
use App\Events\Models\DomainAnalytics\DomainAnalyticsUpdated;
use App\Events\Models\DomainAnalytics\DomainAnalyticsUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAnalytics extends Model
{
    use HasFactory;

    public $fillable = [
        'query_name',
        'response_code',
        'origin',
        'query_count',
        'uncached_count',
        'stale_count',
        'date',
    ];

    public $dispatchesEvents = [
        'created' => DomainAnalyticsCreated::class,
        'creating' => DomainAnalyticsCreating::class,
        'deleting' => DomainAnalyticsDeleting::class,
        'deleted' => DomainAnalyticsDeleted::class,
        'updating' => DomainAnalyticsUpdating::class,
        'updated' => DomainAnalyticsUpdated::class,
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
