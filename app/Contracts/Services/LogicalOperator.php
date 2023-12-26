<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface LogicalOperator
{
    // The goal of a logical operator is to compare value 1 to value 2
    public function compute(mixed $firstValue, mixed $secondValue): bool;

    public function butTheOpposite(mixed $firstValue, mixed $secondValue): bool;

    public function inverse(mixed $firstValue, mixed $secondValue): bool;
}
