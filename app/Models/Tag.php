<?php

declare(strict_types=1);

namespace App\Models;

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

    public $guarded = [];

    public $dispatchesEvents = [
        'created' => TagCreated::class,
        'creating' => TagCreating::class,
        'deleting' => TagDeleting::class,
        'deleted' => TagDeleted::class,
        'updating' => TagUpdating::class,
        'updated' => TagUpdated::class,
    ];

    public function articles()
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }

    public function feeds()
    {
        return $this->morphedByMany(ExternalRssFeed::class, 'taggable');
    }

    public function servers()
    {
        return $this->morphedByMany(Server::class, 'taggable');
    }

    public function transactions()
    {
        return $this->morphedByMany(Transaction::class, 'taggable');
    }

    public function projects()
    {
        return $this->morphedByMany(Project::class, 'taggable');
    }

    public function budgets()
    {
        return $this->morphedByMany(Budget::class, 'taggable');
    }

    public function accounts()
    {
        return $this->morphedByMany(Account::class, 'taggable');
    }

    public function domains()
    {
        return $this->morphedByMany(Domain::class, 'taggable');
    }

    public function people()
    {
        return $this->morphedByMany(Person::class, 'taggable');
    }
}
