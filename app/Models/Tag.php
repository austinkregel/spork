<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphedByMany;
use App\Contracts\Conditionable;
use App\Contracts\ModelQuery;
use App\Events\Models\Tag\TagCreated;
use App\Events\Models\Tag\TagCreating;
use App\Events\Models\Tag\TagDeleted;
use App\Events\Models\Tag\TagDeleting;
use App\Events\Models\Tag\TagUpdated;
use App\Events\Models\Tag\TagUpdating;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use App\Models\Traits\HasConditions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property bool $must_all_conditions_pass
 */
class Tag extends \Spatie\Tags\Tag implements Conditionable, Crud, ModelQuery
{
    // Tags with conditions will essentially only be applied if the conditions pass.
    use HasConditions, HasFactory;

    public $fillable = [
        'name',
        'slug',
        'must_all_conditions_pass',
        'type',
        'order_column',
    ];

    public $dispatchesEvents = [
        'created' => TagCreated::class,
        'creating' => TagCreating::class,
        'deleting' => TagDeleting::class,
        'deleted' => TagDeleted::class,
        'updating' => TagUpdating::class,
        'updated' => TagUpdated::class,
    ];

    public function articles(): MorphedByMany
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }

    public function feeds(): MorphedByMany
    {
        return $this->morphedByMany(ExternalRssFeed::class, 'taggable');
    }

    public function servers(): MorphedByMany
    {
        return $this->morphedByMany(Server::class, 'taggable');
    }

    public function transactions(): MorphedByMany
    {
        return $this->morphedByMany(Transaction::class, 'taggable');
    }

    public function projects(): MorphedByMany
    {
        return $this->morphedByMany(Project::class, 'taggable');
    }

    public function budgets(): MorphedByMany
    {
        return $this->morphedByMany(Budget::class, 'taggable');
    }

    public function accounts(): MorphedByMany
    {
        return $this->morphedByMany(Account::class, 'taggable');
    }

    public function domains(): MorphedByMany
    {
        return $this->morphedByMany(Domain::class, 'taggable');
    }

    public function people(): MorphedByMany
    {
        return $this->morphedByMany(Person::class, 'taggable');
    }

    public function messages(): MorphedByMany
    {
        return $this->morphedByMany(Message::class, 'taggable');
    }
}
