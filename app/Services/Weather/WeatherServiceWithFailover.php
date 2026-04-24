<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Contracts\Services\WeatherServiceContract;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class WeatherServiceWithFailover implements WeatherServiceContract
{
    protected WeatherServiceContract $primary_service;

    protected WeatherServiceContract $fallback_service;

    protected LoggerInterface $logger;

    public function __construct(
        ?OpenWeatherService $primary_service = null,
        ?WeatherApiService $fallback_service = null,
        ?LoggerInterface $logger = null
    ) {
        $this->primary_service = $primary_service ?? app(OpenWeatherService::class);
        $this->fallback_service = $fallback_service ?? app(WeatherApiService::class);
        $this->logger = $logger ?? Log::channel('weather');
    }

    public function query(string $address): array
    {
        $result = $this->primary_service->query($address);

        // OpenWeatherService returns array with single forecast
        // WeatherApiService returns array of forecasts keyed by date
        // Normalize to ensure we have at least one forecast
        if (empty($result)) {
            throw new \RuntimeException('Primary weather service returned empty result');
        }

        return $result;
    }
}
