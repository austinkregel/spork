<?php

declare(strict_types=1);

namespace App\Models\Spork;

use App\Models\Crud;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Script extends Model implements Crud
{
    use HasFactory;
}
