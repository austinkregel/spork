<?php

declare(strict_types=1);

namespace App\Services\Facades;

use Illuminate\Support\Facades\Facade;

final class Operator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'operator';
    }
}
