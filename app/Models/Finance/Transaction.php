<?php

namespace App\Models\Finance;

use App\Events\Models\Transaction\TransactionCreated;
use App\Events\Models\Transaction\TransactionCreating;
use App\Events\Models\Transaction\TransactionDeleted;
use App\Events\Models\Transaction\TransactionDeleting;
use App\Events\Models\Transaction\TransactionUpdated;
use App\Events\Models\Transaction\TransactionUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public $dispatchesEvents = [
        'created' => TransactionCreated::class,
        'creating' => TransactionCreating::class,
        'deleting' => TransactionDeleting::class,
        'deleted' => TransactionDeleted::class,
        'updating' => TransactionUpdating::class,
        'updated' => TransactionUpdated::class,
    ];
}
