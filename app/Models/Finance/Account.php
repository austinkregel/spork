<?php

namespace App\Models\Finance;

use App\Events\Models\Account\AccountCreated;
use App\Events\Models\Account\AccountCreating;
use App\Events\Models\Account\AccountDeleted;
use App\Events\Models\Account\AccountDeleting;
use App\Events\Models\Account\AccountUpdated;
use App\Events\Models\Account\AccountUpdating;
use App\Models\Credential;
use App\Models\Crud;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model implements Crud
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'mask',
        'name',
        'official_name',
        'balance',
        'available',
        'subtype',
        'type',
        'access_token_id',
    ];

    public $dispatchesEvents = [
        'created' => AccountCreated::class,
        'creating' => AccountCreating::class,
        'deleting' => AccountDeleting::class,
        'deleted' => AccountDeleted::class,
        'updating' => AccountUpdating::class,
        'updated' => AccountUpdated::class,
    ];

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }
}
