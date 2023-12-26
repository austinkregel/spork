<?php

declare(strict_types=1);

namespace App\Services\Condition;

use Illuminate\Support\Str;

class StartsWithOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        return Str::startsWith($haystack, $needle);
    }
}
