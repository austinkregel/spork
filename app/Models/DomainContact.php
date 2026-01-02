<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'role',
        'name',
        'email',
        'phone',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}



















