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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model implements Crud
{
    use HasFactory;

    public $dispatchesEvents = [
        'created' => BudgetCreated::class,
        'creating' => BudgetCreating::class,
        'deleting' => BudgetDeleting::class,
        'deleted' => BudgetDeleted::class,
        'updating' => BudgetUpdating::class,
        'updated' => BudgetUpdated::class,
    ];
}
