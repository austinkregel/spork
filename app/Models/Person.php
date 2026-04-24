<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Events\Models\Person\PersonCreated;
use App\Events\Models\Person\PersonCreating;
use App\Events\Models\Person\PersonDeleted;
use App\Events\Models\Person\PersonDeleting;
use App\Events\Models\Person\PersonUpdated;
use App\Events\Models\Person\PersonUpdating;
use App\Jobs\Crm\SyncPersonToMonica;
use App\Models\Traits\HasProjectResource;
use App\Models\Traits\ScopeRelativeSearch;
use App\Observers\ApplyCredentialsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;

#[ObservedBy([ApplyCredentialsObserver::class])]
class Person extends Model implements Crud, ModelQuery, Taggable
{
    use HasFactory;
    use HasProjectResource;
    use HasTags;
    use LogsActivity;
    use ScopeRelativeSearch;
    use Searchable;

    public $guarded = [];

    public $dispatchesEvents = [
        'created' => PersonCreated::class,
        'creating' => PersonCreating::class,
        'deleting' => PersonDeleting::class,
        'deleted' => PersonDeleted::class,
        'updating' => PersonUpdating::class,
        'updated' => PersonUpdated::class,
    ];

    protected static function booted(): void
    {
        static::saved(function (self $person): void {
            if (! $person->user_id) {
                return;
            }

            if (! $person->wasRecentlyCreated && ! $person->wasChanged([
                'name',
                'primary_email',
                'primary_number',
                'primary_address',
                'birthdate',
                'phone_numbers',
                'addresses',
                'emails',
                'identifiers',
                'names',
                'locality',
                'jobs',
                'education',
                'photo_url',
                'pronouns',
            ])) {
                return;
            }

            SyncPersonToMonica::dispatch($person);
        });
    }

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'phone_numbers' => 'array',
            'addresses' => 'array',
            'emails' => 'array',
            'names' => 'array',
            'identifiers' => 'array',
            'locality' => 'array',
            'jobs' => 'array',
            'education' => 'array',
        ];
    }

    #[Scope]
    protected function q(Builder $query, string $string): void
    {
        $query->where('name', 'like', '%'.$string.'%');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'primary_email',
                'primary_number',
                'primary_address',
                'birthdate',
                'pronouns',
                'photo_url',
                'phone_numbers',
                'addresses',
                'emails',
                'names',
                'identifiers',
                'locality',
                'jobs',
                'education',
            ])
            ->useLogName('person')
            ->dontSubmitEmptyLogs()
            ->logOnlyDirty();
    }
}
