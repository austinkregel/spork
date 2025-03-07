<?php

declare(strict_types=1);

namespace App\Services\Condition;

class ArrayContainsValueOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        if (! is_array($haystack) && str_contains($needle, ',')) {
            $tmpNeedle = $haystack;

            $haystack = explode(',', $needle);
            $needle = $tmpNeedle;
        }

        return (new ContainsValueOperator)->compute(haystack: $haystack, needle: $needle);
    }
}
