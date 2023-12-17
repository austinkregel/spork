<?php

namespace App\Services\Condition;

use Illuminate\Support\Str;

class EndsWithOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        return Str::endsWith($haystack, $needle);
    }
}
