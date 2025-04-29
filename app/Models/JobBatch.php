<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobBatch extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'total_jobs',
        'pending_jobs',
        'failed_jobs',
        'failed_job_ids',
        'options',
        'cancelled_at',
        'created_at',
        'finished_at',
    ];

    public $timestamps = false;

    public $dispatchesEvents = [
        'created' => \App\Events\Models\JobBatch\JobBatchCreated::class,
        'updated' => \App\Events\Models\JobBatch\JobBatchUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'failed_job_ids' => 'json',
        ];
    }
}
