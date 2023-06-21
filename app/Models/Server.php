<?php

namespace App\Models;

use App\Contracts\ModelQuery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Tags\HasTags;

class Server extends Model implements ModelQuery
{
    use HasFactory, HasTags;
    public $guarded = [];
}
