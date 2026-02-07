<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\Event\EventCreated;
use App\Events\Models\Event\EventCreating;
use App\Events\Models\Event\EventDeleted;
use App\Events\Models\Event\EventDeleting;
use App\Events\Models\Event\EventUpdated;
use App\Events\Models\Event\EventUpdating;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RRule\RRule;
use Spatie\Tags\HasTags;

class Event extends Model implements Crud, Taggable
{
    use HasFactory;
    use HasTags;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'rrule',
        'color',
    ];

    public $dispatchesEvents = [
        'created' => EventCreated::class,
        'creating' => EventCreating::class,
        'deleting' => EventDeleting::class,
        'deleted' => EventDeleted::class,
        'updating' => EventUpdating::class,
        'updated' => EventUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all occurrences of this event within the given date range.
     * If the event has an RRule, expands it; otherwise returns a single occurrence.
     *
     * @return array<array{start: Carbon, end: Carbon}>
     */
    public function getOccurrences(Carbon $start, Carbon $end): array
    {
        $occurrences = [];

        if ($this->rrule) {
            try {
                $rrule = new RRule($this->rrule, $this->start_at);
                $rruleOccurrences = $rrule->getOccurrencesBetween($start, $end);

                foreach ($rruleOccurrences as $occurrence) {
                    $duration = $this->start_at->diffInSeconds($this->end_at);
                    $occurrences[] = [
                        'start' => Carbon::instance($occurrence),
                        'end' => Carbon::instance($occurrence)->addSeconds($duration),
                    ];
                }
            } catch (\Exception $e) {
                // If RRule parsing fails, fall back to single occurrence
                if ($this->start_at->lte($end) && $this->end_at->gte($start)) {
                    $occurrences[] = [
                        'start' => $this->start_at,
                        'end' => $this->end_at,
                    ];
                }
            }
        } else {
            // Single occurrence event
            if ($this->start_at->lte($end) && $this->end_at->gte($start)) {
                $occurrences[] = [
                    'start' => $this->start_at,
                    'end' => $this->end_at,
                ];
            }
        }

        return $occurrences;
    }

    /**
     * Check if this event is recurring.
     */
    public function isRecurring(): bool
    {
        return ! empty($this->rrule);
    }
}
