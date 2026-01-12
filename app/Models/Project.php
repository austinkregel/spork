<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Events\Models\Project\ProjectCreated;
use App\Events\Models\Project\ProjectCreating;
use App\Events\Models\Project\ProjectDeleted;
use App\Events\Models\Project\ProjectDeleting;
use App\Events\Models\Project\ProjectUpdated;
use App\Events\Models\Project\ProjectUpdating;
use App\Models\Finance\Account as FinanceAccount;
use App\Models\Finance\Budget as FinanceBudget;
use App\Models\Finance\Transaction as FinanceTransaction;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use App\Observers\ApplyCredentialsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;

#[ObservedBy([ApplyCredentialsObserver::class])]
class Project extends Model implements Crud, ModelQuery, Taggable
{
    use HasFactory;
    use HasTags;
    use LogsActivity;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    public $guarded = [];

    public $dispatchesEvents = [
        'created' => ProjectCreated::class,
        'creating' => ProjectCreating::class,
        'deleting' => ProjectDeleting::class,
        'deleted' => ProjectDeleted::class,
        'updating' => ProjectUpdating::class,
        'updated' => ProjectUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'json',
        ];
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

    /**
     * These are credentials that are explicitly assigned to the project.
     */
    public function credentials(): MorphToMany
    {
        return $this->morphedByMany(
            Credential::class,
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

    public function domains(): MorphToMany
    {
        return $this->morphedByMany(
            Domain::class,
            'resource',
            'project_resources'
        );
    }

    public function budgets(): MorphToMany
    {
        return $this->morphedByMany(
            FinanceBudget::class,
            'resource',
            'project_resources'
        );
    }

    public function accounts(): MorphToMany
    {
        return $this->morphedByMany(
            FinanceAccount::class,
            'resource',
            'project_resources'
        );
    }

    public function transactions(): MorphToMany
    {
        return $this->morphedByMany(
            FinanceTransaction::class,
            'resource',
            'project_resources'
        );
    }

    public function externalRssFeeds(): MorphToMany
    {
        return $this->morphedByMany(
            ExternalRssFeed::class,
            'resource',
            'project_resources'
        );
    }

    public function people(): MorphToMany
    {
        return $this->morphedByMany(
            Person::class,
            'resource',
            'project_resources'
        );
    }

    public function threads(): MorphToMany
    {
        return $this->morphedByMany(
            Thread::class,
            'resource',
            'project_resources'
        );
    }

    public function automations(): MorphToMany
    {
        return $this->morphedByMany(
            Automation::class,
            'resource',
            'project_resources'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function credentialFor(string $service): ?Credential
    {
        $credential = $this->credentials()->where('service', $service)->first();

        return $credential;
    }

    public function owner(): MorphMany
    {
        return $this->morphMany(User::class, 'owner');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'team_id'])
            ->useLogName('project')
            ->dontSubmitEmptyLogs()
            ->logOnlyDirty();
    }
}
