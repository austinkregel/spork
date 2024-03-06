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
use App\Models\Taggable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class Transaction extends Model implements Crud, ModelQuery, Taggable
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

        'personal_finance_category',
        'personal_finance_category_detailed',
        'personal_finance_icon',
        'seller_icon',

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

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function user()
    {
        return $this->hasManyThrough(User::class, Account::class);
    }
}
