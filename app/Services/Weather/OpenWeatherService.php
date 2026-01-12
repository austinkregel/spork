<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Contracts\Services\GeocodingServiceContract;
use App\Contracts\Services\WeatherServiceContract;
use App\Forecast;
use Carbon\Carbon;
use GuzzleHttp\Client;

class OpenWeatherService implements WeatherServiceContract
{
    protected Client $client;

    protected GeocodingServiceContract $geocoding_service;

    public function __construct(
        ?Client $client = null,
        ?GeocodingServiceContract $geocoding_service = null
    ) {
        $this->client = $client ?? new Client([
            'timeout' => config('services.openweather.timeout_seconds', 10),
            'connect_timeout' => config('services.openweather.timeout_seconds', 10),
        ]);
        $this->geocoding_service = $geocoding_service ?? app(GeocodingServiceContract::class);
    }

    public function query(string $address): array
    {
        $geocoded = $this->geocoding_service->geocode($address);

        if ($geocoded['latitude'] === null || $geocoded['longitude'] === null) {
            throw new \RuntimeException(sprintf('Failed to geocode address: %s', $address));
        }

        $latitude = $geocoded['latitude'];
        $longitude = $geocoded['longitude'];

        $cache_key = sprintf('openweather:%s:%s:%s', md5($address), $latitude, $longitude);
        $cache_ttl = now()->addMinutes((int) config('services.openweather.cache_ttl_minutes', 30));

        $weather = cache()->remember(
            $cache_key,
            $cache_ttl,
            fn () => $this->fetchWeatherData($latitude, $longitude)
        );

        // v2.5 API provides timezone as offset in seconds, convert to timezone name
        $timezone = $this->getTimezoneFromOffset($weather->timezone ?? null, $geocoded);

        $currently_day_time = now()->isBetween(
            Carbon::parse($weather->sys->sunrise, 'UTC')->setTimezone($timezone),
            Carbon::parse($weather->sys->sunset, 'UTC')->setTimezone($timezone),
        );

        $forecast = new Forecast;
        $forecast->updated_at = Carbon::parse($weather->dt, 'UTC')->setTimezone($timezone)->format('Y-m-d H:i');
        $forecast->condition = $weather->weather[0]->description;
        $forecast->condition_url = sprintf('https://openweathermap.org/img/wn/%s.png', $weather->weather[0]->icon);
        $forecast->condition_image = $this->getConditionImage($weather->weather[0]->description, $currently_day_time);

        $forecast->address = $geocoded['address'] ?? $address;
        $forecast->temperature = $weather->main->temp;
        $forecast->feels_like = $weather->main->feels_like;
        $forecast->humidity = $weather->main->humidity;
        $forecast->pressure = $weather->main->pressure;
        $forecast->wind_speed = $weather->wind->speed;
        $forecast->cloud_cover = $weather->clouds->all;
        $forecast->chance_of_rain = 0; // v2.5 API doesn't provide precipitation probability
        $forecast->chance_of_snow = $weather->snow->all ?? 0;
        $forecast->sunset = Carbon::parse($weather->sys->sunset, 'UTC')->setTimezone($timezone)->format('Y-m-d H:i');
        $forecast->sunrise = Carbon::parse($weather->sys->sunrise, 'UTC')->setTimezone($timezone)->format('Y-m-d H:i');

        return [
            $forecast,
        ];
    }

    protected function fetchWeatherData(float $latitude, float $longitude): object
    {
        $base_url = config('services.openweather.base_url', 'https://api.openweathermap.org/data/2.5/weather');
        $api_key = config('services.openweather.api_key');
        $units = config('services.openweather.units', 'imperial');

        if (empty($api_key)) {
            throw new \RuntimeException('OpenWeather API key is not configured');
        }

        // Base URL may already include /weather, so check and construct URL accordingly
        $url = str_contains($base_url, '?')
            ? sprintf('%s&lat=%s&lon=%s&appid=%s&units=%s', $base_url, $latitude, $longitude, $api_key, $units)
            : sprintf('%s?lat=%s&lon=%s&appid=%s&units=%s', $base_url, $latitude, $longitude, $api_key, $units);

        $response = $this->client->get($url)->getBody()->getContents();

        return json_decode($response);
    }

    protected function getTimezoneFromOffset(?int $timezone_offset, array $geocoded): string
    {
        // Try to get timezone from geocoded data if available
        if (isset($geocoded['timezone'])) {
            return $geocoded['timezone'];
        }

        // v2.5 API provides timezone as offset in seconds
        // Convert to a timezone name if possible, otherwise use default
        if ($timezone_offset !== null) {
            // Try to find a timezone matching the offset
            $offset_hours = $timezone_offset / 3600;
            $timezones = timezone_identifiers_list();

            foreach ($timezones as $tz) {
                $tz_offset = (new \DateTimeZone($tz))->getOffset(new \DateTime('now', new \DateTimeZone('UTC'))) / 3600;
                if ($tz_offset == $offset_hours) {
                    // Prefer US timezones for US locations
                    if (str_contains($tz, 'America/')) {
                        return $tz;
                    }
                }
            }
        }

        // Fall back to config default
        return config('services.openweather.default_timezone', 'America/Detroit');
    }

    protected function getConditionImage(string $description, bool $is_day_time): string
    {
        // The remaining weather conditions should be based on whats available at https://openweathermap.org/weather-conditions
        return match ($description) {
            'rain', 'freezing rain', 'heavy rain', 'shower rain', 'moderate rain', 'light rain', 'heavy intensity rain', 'very heavy rain', 'extreme rain', 'light intensity shower rain', 'heavy intensity shower rain', 'ragged shower rain' => '🌧',
            'snow', 'light snow', 'heavy snow', 'sleet', 'shower sleet', 'light shower sleet', 'rain and snow', 'light rain and snow', 'light shower snow', 'shower snow', 'heavy shower snow' => '🌨️',
            'light intensity drizzle', 'drizzle', 'heavy intensity drizzle', 'light intensity drizzle rain', 'drizzle rain', 'heavy intensity drizzle rain', 'shower rain and drizzle', 'heavy shower rain and drizzle', 'shower drizzle' => '🌧️',
            'thunderstorm with light rain', 'thunderstorm with rain', 'thunderstorm with heavy rain', 'light thunderstorm', 'thunderstorm', 'heavy thunderstorm', 'ragged thunderstorm', 'thunderstorm with light drizzle', 'thunderstorm with drizzle', 'thunderstorm with heavy drizzle' => '🌩️',
            'mist', 'smoke', 'haze', 'sand, dust whirls', 'fog', 'sand', 'dust', 'volcanic ash', 'squalls', 'tornado' => '🌫💨',
            'clear sky' => $is_day_time ? '☀️' : '🌙',
            'few clouds' => $is_day_time ? '🌤' : '🌙☁️',
            'scattered clouds' => $is_day_time ? '⛅️' : '🌙☁️',
            'broken clouds' => '🌥',
            'overcast clouds' => '☁️',
            default => '❓',
        };
    }
}
