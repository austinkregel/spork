<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAnalytics extends Model
{
    use HasFactory;

    public $fillable = [
        'query_name',
        'response_code',
        'origin',
        'query_count',
        'uncached_count',
        'stale_count',
        'date',
    ];
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
