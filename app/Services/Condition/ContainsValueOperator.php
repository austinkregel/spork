<?php

declare(strict_types=1);

namespace App\Services\Condition;

class ContainsValueOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        // Guard: empty needles would match everything (e.g. str_contains('foo', '') === true).
        if ($needle === null) {
            return false;
        }

        if (is_string($needle) && trim($needle) === '') {
            return false;
        }

        if (is_array($haystack)) {
            // Preserve the original "in_array" semantics for exact matches first.
            if (in_array($needle, $haystack)) {
                return true;
            }

            foreach ($haystack as $item) {
                if (str_contains(strtolower((string) $item), strtolower((string) $needle))) {
                    return true;
                }
            }

            return false;
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
