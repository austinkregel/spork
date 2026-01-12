<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyCard extends Model
{
    use HasFactory;

    public $fillable = [
        'credential_id',
        'card_token',
        'state',
        'type',
        'memo',
        'descriptor',
        'spend_limit_cents',
        'spend_limit_duration',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'spend_limit_cents' => 'integer',
            'data' => 'array',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
