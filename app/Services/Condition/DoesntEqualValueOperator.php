<?php

declare(strict_types=1);

namespace App\Services\Condition;

class DoesntEqualValueOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        return (new EqualsValueOperator)->butTheOpposite($needle, $haystack);
    }
}
