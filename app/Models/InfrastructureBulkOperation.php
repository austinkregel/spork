<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrastructureBulkOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'operation',
        'scope',
        'filter',
        'payload',
        'status',
        'job_id',
        'result',
        'queued_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'queued_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
