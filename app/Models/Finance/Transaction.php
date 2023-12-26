<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Contracts\ModelQuery;
use App\Events\Models\Transaction\TransactionCreated;
use App\Events\Models\Transaction\TransactionCreating;
use App\Events\Models\Transaction\TransactionDeleted;
use App\Events\Models\Transaction\TransactionDeleting;
use App\Events\Models\Transaction\TransactionUpdated;
use App\Events\Models\Transaction\TransactionUpdating;
use App\Models\Crud;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class Transaction extends Model implements Crud, ModelQuery
{
    use HasFactory, HasTags;

    public $fillable = [
        'name',
        'amount',
        'account_id',
        'date',
        'pending',
        'category_id',
        'transaction_id',
        'transaction_type',
        'pending_transaction_id',
        'data',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'datetime',
        'pending' => 'boolean',
        'data' => 'json',
    ];

    public $dispatchesEvents = [
        'created' => TransactionCreated::class,
        'creating' => TransactionCreating::class,
        'deleting' => TransactionDeleting::class,
        'deleted' => TransactionDeleted::class,
        'updating' => TransactionUpdating::class,
        'updated' => TransactionUpdated::class,
    ];
}
