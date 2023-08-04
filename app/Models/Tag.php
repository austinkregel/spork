<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends \Spatie\Tags\Tag implements ModelQuery
{
    use HasFactory;

    public $guarded = [];
}
