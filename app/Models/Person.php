<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model implements ModelQuery
{
    use HasFactory;

    public $guarded = [];
}
