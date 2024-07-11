<?php

declare(strict_types=1);

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
    ],

];
