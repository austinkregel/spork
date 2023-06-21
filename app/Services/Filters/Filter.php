<?php

declare(strict_types=1);

namespace App\Services\Filters;

abstract class Filter
{
    abstract public function filter($vendorValue): array;
}
