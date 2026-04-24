<?php

declare(strict_types=1);

/**
 * For external services
 */

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'plaid' => [
        'env' => env('PLAID_ENV', 'sandbox'),
        'secret_key' => env('PLAID_PRODUCTION_SECRET', ''),
        'client_id' => env('PLAID_CLIENT_ID', ''),
        'client_name' => env('APP_NAME'),
        'language' => env('PLAID_LANGUAGE', 'en'),
        'country_codes' => explode(',', env('PLAID_COUNTRY_CODES', 'US')),
        'products' => ['transactions'],
        'version' => env('PLAID_VERSION', '2020-09-14'),
    ],

    'laravelpassport' => [
        'client_id' => env('LARAVEL_PASSPORT_CLIENT_ID'),
        'client_secret' => env('LARAVEL_PASSPORT_CLIENT_SECRET'),
        'redirect' => env('LARAVEL_PASSPORT_REDIRECT'),
        'host' => env('LARAVEL_PASSPORT_HOST'),
    ],

    'matrix' => [
        'url' => env('MATRIX_HOST'),
    ],

    'monica' => [
        'base_url' => env('MONICA_BASE_URL', 'https://monica.kregel.host/api'),
        'service' => env('MONICA_SERVICE_NAME', \App\Models\Credential::MONICA),
    ],

    'command_server' => [
        'ws_url' => env('COMMAND_SERVER_WS_URL', ''),
    ],

    'openweather' => [
        'api_key' => env('OPEN_WEATHER_KEY'),
        'base_url' => env('OPEN_WEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5/weather'),
        'cache_ttl_minutes' => env('OPEN_WEATHER_CACHE_TTL_MINUTES', 30),
        'default_timezone' => env('OPEN_WEATHER_DEFAULT_TIMEZONE', 'America/Detroit'),
        'units' => env('OPEN_WEATHER_UNITS', 'imperial'),
        'timeout_seconds' => env('OPEN_WEATHER_TIMEOUT_SECONDS', 10),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'geocode_base_url' => env('GOOGLE_MAPS_GEOCODE_BASE_URL', 'https://maps.googleapis.com/maps/api/geocode/json'),
        'places_base_url' => env('GOOGLE_MAPS_PLACES_BASE_URL', 'https://maps.googleapis.com/maps/api/place/textsearch/json'),
        'business_search_cache_ttl_days' => env('GOOGLE_MAPS_BUSINESS_SEARCH_CACHE_TTL_DAYS', 1),
        'business_search_radius' => env('GOOGLE_MAPS_BUSINESS_SEARCH_RADIUS', 321869),
        'business_search_location' => env('GOOGLE_MAPS_BUSINESS_SEARCH_LOCATION', 'michigan'),
    ],
];
