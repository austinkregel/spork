<?php

namespace App\Services\Condition;

class EqualsValueOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        return $needle === $haystack;
    }
}
