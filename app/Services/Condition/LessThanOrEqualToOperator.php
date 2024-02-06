<?php

declare(strict_types=1);

namespace App\Services\Condition;

class LessThanOrEqualToOperator extends AbstractLogicalOperator
{
    public function compute(mixed $valueFromCondition, mixed $valueFromParameter): bool
    {
        return (new LessThanOperator)->compute($valueFromCondition, $valueFromParameter) || $valueFromCondition === $valueFromParameter;
    }
}
