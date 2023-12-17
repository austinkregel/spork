<?php

namespace App\Services\Condition;

class DoesntContainValueOperator extends AbstractLogicalOperator
{
    public function compute(mixed $needle, mixed $haystack): bool
    {
        return (new ContainsValueOperator)->butTheOpposite($needle, $haystack);
    }
}
