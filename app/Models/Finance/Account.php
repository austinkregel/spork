<?php

namespace App\Models\Finance;

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

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }
}
