<?php

declare(strict_types=1);

namespace App\Services\Condition;

class GreaterThanOrEqualToOperator extends AbstractLogicalOperator
{
    public function compute(mixed $firstValue, mixed $secondValue): bool
    {
        return (new GreaterThanOperator)->compute($firstValue, $secondValue) || $firstValue === $secondValue;
    }
}
