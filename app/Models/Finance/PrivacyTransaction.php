<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Tags\HasTags;

class PrivacyTransaction extends Model
{
    use HasFactory;
    use HasTags;

    public $fillable = [
        'credential_id',
        'privacy_transaction_id',
        'result',
        'status',
        'amount_cents',
        'currency_code',
        'date_authorized',
        'date_settled',
        'descriptor',
        'memo',
        'mcc',
        'card_uuid',
        'card_id',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'date_authorized' => 'datetime',
            'date_settled' => 'datetime',
            'data' => 'array',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'privacy_transaction_matches', 'privacy_transaction_id', 'transaction_id')
            ->withPivot(['match_method', 'confidence'])
            ->withTimestamps();
    }
}
