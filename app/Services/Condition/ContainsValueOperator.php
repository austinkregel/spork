<?php

namespace App\Services\Condition;

class ContainsValueOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        if (is_array($haystack)) {
            return in_array($needle, $haystack);
        }

        if (is_object($haystack)) {
            return isset($haystack->$needle);
        }

        return str_contains(strtolower((string) $haystack), strtolower($needle));
    }
}
