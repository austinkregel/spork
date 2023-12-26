<?php

declare(strict_types=1);

namespace App\Services\Condition;

use App\Contracts\LogicalOperator;

abstract class AbstractLogicalOperator implements LogicalOperator
{
    public function inverse(mixed $value1, mixed $value2): bool
    {
        return ! $this->compute($value1, $value2);
    }

    public function butTheOpposite(mixed $firstValue, mixed $secondValue): bool
    {
        return $this->inverse($firstValue, $secondValue);
    }
}
