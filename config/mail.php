<?php

declare(strict_types=1);

return [

    'mailers' => [
        'mailgun' => [
            'transport' => 'mailgun',
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],
    ],

    'boxes' => [

        'imap' => [
            'transport' => 'imap',
            'host' => env('IMAP_HOST', 'smtp.mailgun.org'),
            'port' => env('IMAP_PORT', 587),
            'encryption' => env('IMAP_ENCRYPTION', 'notls'),
            'username' => env('IMAP_USERNAME'),
            'password' => env('IMAP_PASSWORD'),
        ],
    ],

];
