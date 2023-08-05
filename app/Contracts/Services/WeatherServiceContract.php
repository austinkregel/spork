<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface WeatherServiceContract
{
    public function query(string $address): array;
}
