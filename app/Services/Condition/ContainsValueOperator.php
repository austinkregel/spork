<?php

declare(strict_types=1);

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

        if (is_null($needle) && ! is_null($haystack)) {
            // if one is null, and the other, then we obvs need to return false;
            return false;
        }

        return str_contains(strtolower((string) $haystack), strtolower($needle));
    }
}
