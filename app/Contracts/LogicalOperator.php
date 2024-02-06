<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface LogicalOperator
{
    public function compute(mixed $haystack, mixed $needle): bool;
}
