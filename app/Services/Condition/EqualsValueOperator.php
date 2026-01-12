<?php

declare(strict_types=1);

namespace App\Services\Condition;

class EqualsValueOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        if (is_array($haystack)) {
            return in_array($needle, $haystack);
        }

        return $needle == $haystack;
    }
}
