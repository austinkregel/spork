<?php

declare(strict_types=1);

namespace App\Services\Condition;

use Carbon\Carbon;

class LessThanOperator extends AbstractLogicalOperator
{
    public function compute(mixed $firstValue, mixed $secondValue): bool
    {
        if (is_array($firstValue) || is_array($secondValue)) {
            return false;
        }

        if (strtotime($firstValue) && strtotime($secondValue)) {
            // we're dealing with a date, or date time.
            return Carbon::parse($firstValue)->isBefore(Carbon::parse($secondValue));
        }

        if (is_string($firstValue) && is_string($secondValue)) {
            // This is meant to be a numeric or date operator, checking the greatness of a string is beyond the scope of this lib.
            return strlen($firstValue) < strlen($secondValue);
        }

        if (! is_numeric($firstValue)) {
            // At the time of writing, I'm not sure what could end up here other than maybe objects/arrays?
            $firstValue = strlen($firstValue);
        }

        if (! is_numeric($secondValue)) {
            $secondValue = strlen($secondValue);
        }

        return $firstValue < $secondValue;
    }
}
