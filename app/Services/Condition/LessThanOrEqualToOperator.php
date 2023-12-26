<?php

declare(strict_types=1);

namespace App\Services\Condition;

class LessThanOrEqualToOperator extends AbstractLogicalOperator
{
    public function compute(mixed $firstValue, mixed $secondValue): bool
    {
        return (new LessThanOperator)->compute($firstValue, $secondValue) || $firstValue === $secondValue;
    }
}
