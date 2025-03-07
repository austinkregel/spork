<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Contracts\Services\GeocodingServiceContract;
use App\Contracts\Services\WeatherServiceContract;

class WeatherGovApiService implements WeatherServiceContract
{
    public function __construct(
        protected GeocodingServiceContract $geocodingService
    ) {}

    public function query(string $address): array
    {
        $latLang = $this->geocodingService->geocode($address);

    }
}
