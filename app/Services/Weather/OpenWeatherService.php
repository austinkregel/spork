<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Contracts\Services\WeatherServiceContract;
use App\Forecast;
use Carbon\Carbon;
use GuzzleHttp\Client;

class OpenWeatherService implements WeatherServiceContract
{
    public function query(string $address): array
    {
        $client = new Client;

        $weather = cache()->remember(
            'current-weather-for-petoskey',
            now()->addMinutes(30),
            fn () => json_decode(
                $client
                    ->get(sprintf('https://api.openweathermap.org/data/2.5/weather?lat=45.3757675&lon=-84.961903&appid=%s&units=imperial', env('OPEN_WEATHER_KEY')))
                    ->getBody()
                    ->getContents()
            )
        );

        $currentlyDayTime = now()->isBetween(
            Carbon::parse($weather->sys->sunrise, 'UTC')->setTimezone('America/Detroit'),
            Carbon::parse($weather->sys->sunset, 'UTC')->setTimezone('America/Detroit'),
        );

        $currentTime = now()->roundHour()->format('Y-m-d H:i');
        $forecast = new Forecast();
        $forecast->updated_at = Carbon::parse($weather->dt, 'UTC')->setTimezone('America/Detroit')->format('Y-m-d H:i');
        $forecast->condition = $weather->weather[0]->description;
        $forecast->condition_url = sprintf('https://openweathermap.org/img/wn/%s.png', $weather->weather[0]->icon);
        $forecast->condition_image = match ($weather->weather[0]->description) {
            // The remaining weather conditions should be based on whats available at https://openweathermap.org/weather-conditions
            'rain', 'freezing rain', 'heavy rain', 'shower rain', 'moderate rain', 'light rain' => '🌧',
            'snow', 'light snow', 'heavy snow', 'sleet', 'shower sleet', 'light shower sleet', 'rain and snow', 'light rain and snow', 'light shower snow', 'shower snow', 'heavy shower snow' => '🌨️',
            'light intensity drizzle','drizzle','heavy intensity drizzle','light intensity drizzle rain','drizzle rain','heavy intensity drizzle rain','shower rain and drizzle','heavy shower rain and drizzle','shower drizzle' => '🌧️',
            'thunderstorm with light rain','thunderstorm with rain','thunderstorm with heavy rain','light thunderstorm','thunderstorm','heavy thunderstorm','ragged thunderstorm','thunderstorm with light drizzle','thunderstorm with drizzle','thunderstorm with heavy drizzle' => '🌩️',
            'light rain','moderate rain','heavy intensity rain','very heavy rain','extreme rain','light intensity shower rain','shower rain','heavy intensity shower rain','ragged shower rain' => '🌧',
            'mist','smoke','haze','sand, dust whirls','fog','sand','dust','volcanic ash','squalls','tornado' => '🌫💨',
            'clear sky' => $currentlyDayTime ? '☀️' : '🌙',
            'few clouds' => $currentlyDayTime ? '🌤' : '🌙☁️',
            'scattered clouds' => $currentlyDayTime ? '⛅️' : '🌙☁️',
            'broken clouds' => '🌥',
            'overcast clouds' => '☁️',
            default => '❓',
        };

        $forecast->address = $address;
        $forecast->temperature = $weather->main->temp;
        $forecast->feels_like = $weather->main->feels_like;
        $forecast->humidity = $weather->main->humidity;
        $forecast->pressure = $weather->main->pressure;
        $forecast->wind_speed = $weather->wind->speed;
        $forecast->cloud_cover = $weather->clouds->all;
        $forecast->chance_of_rain = 0;
        $forecast->chance_of_snow = $weather->snow->all ?? 0;
        $forecast->sunset = Carbon::parse($weather->sys->sunset, 'UTC')->setTimezone('America/Detroit')->format('Y-m-d H:i');
        $forecast->sunrise = Carbon::parse($weather->sys->sunrise, 'UTC')->setTimezone('America/Detroit')->format('Y-m-d H:i');

        return [
            $forecast,
        ];
    }
}
