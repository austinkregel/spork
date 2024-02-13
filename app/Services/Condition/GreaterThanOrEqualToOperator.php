<?php

declare(strict_types=1);

namespace App\Services\Condition;

class GreaterThanOrEqualToOperator extends AbstractLogicalOperator
{
    public function compute(mixed $valueFromCondition, mixed $valueFromParameter): bool
    {
        return (new GreaterThanOperator)->compute($valueFromCondition, $valueFromParameter) || $valueFromCondition === $valueFromParameter;
    }
}
