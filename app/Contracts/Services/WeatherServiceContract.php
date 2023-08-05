<?php

namespace App\Contracts\Services;

interface WeatherServiceContract
{
    public function query(string $address): array;
}
