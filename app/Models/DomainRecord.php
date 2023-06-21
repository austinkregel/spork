<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainRecord extends Model implements ModelQuery
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'ttl',
        'content',
        'comment',
        'tags',
        'value',
        'timeout',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
