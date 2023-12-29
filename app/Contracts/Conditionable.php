<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Conditionable
{
    public function conditions(): MorphMany;
}
