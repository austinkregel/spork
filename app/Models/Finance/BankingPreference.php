<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankingPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pinned_accounts',
        'pinned_budgets',
        'settings',
    ];

    protected $casts = [
        'pinned_accounts' => 'array',
        'pinned_budgets' => 'array',
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}




