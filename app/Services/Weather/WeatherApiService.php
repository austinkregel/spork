<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Contracts\Services\WeatherServiceContract;
use App\Forecast;
use Carbon\Carbon;
use GuzzleHttp\Client;

class WeatherApiService implements WeatherServiceContract
{
    public function query(string $address): array
    {
        $client = new Client;

        $weather = cache()->remember(
            'key',
            now(),
            fn () => json_decode(
                $client
                    ->get(sprintf('http://api.weatherapi.com/v1/forecast.json?key=%s&q=%s&aqi=no&alerts=yes&days=3', env('WEATHER_API_KEY'), $address))
                    ->getBody()
                    ->getContents()
            )
        );

        $currentTime = Carbon::parse($weather->location->localtime, $weather->location->tz_id);
        $key = (int) $currentTime->format('H');

        $forecasts = [];
        foreach ($weather->forecast->forecastday as $forecastday) {
            $forecast = new Forecast();
            $forecast->address = $address;

            $hourForecast = $forecastday->hour[$key];

            $forecast->updated_at = Carbon::parse($hourForecast->time, $weather->location->tz_id)->format('Y-m-d H:s:i');
            $forecast->condition = $hourForecast->condition->text;
            $forecast->temperature = $hourForecast->temp_f;
            $forecast->feels_like = $hourForecast->feelslike_f;
            $forecast->humidity = $hourForecast->humidity;
            $forecast->pressure = $hourForecast->pressure_mb;
            $forecast->wind_speed = $hourForecast->gust_mph;
            $forecast->cloud_cover = $hourForecast->cloud;
            $forecast->chance_of_rain = $hourForecast->chance_of_rain;
            $forecast->chance_of_snow = $hourForecast->chance_of_snow;

            $forecasts[Carbon::parse($hourForecast->time, $weather->location->tz_id)->format('Y-m-d')] = $forecast;
        }

        return $forecasts;
    }
}
