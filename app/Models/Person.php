<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Person extends Model implements ModelQuery
{
    use HasFactory;

    public $guarded = [];

    public $casts = [
        'birthdate' => 'date',
        'phone_numbers' => 'array',
        'addresses' => 'array',
        'emails' => 'array',
        'names' => 'array',
        'identifiers' => 'array',
        'locality' => 'array',
        'jobs' => 'array',
        'education' => 'array',
    ];


    public function scopeQ(Builder $query, string $string): void
    {
        $query->where('name', 'like', '%'.$string.'%');
    }

}
