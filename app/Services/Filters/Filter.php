<?php

namespace App\Services\Filters;

abstract class Filter
{
    abstract public function filter($vendorValue): array;
}
