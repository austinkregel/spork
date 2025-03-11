<?php

declare(strict_types=1);

namespace App\Services\Condition;

class ContainsValueStrictOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        if (is_array($haystack)) {
            return in_array($needle, $haystack, true);
        }

        if (is_object($haystack)) {
            return isset($haystack->$needle);
        }

        return str_contains((string) $haystack, $needle);
    }
}
