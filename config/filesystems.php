<?php

declare(strict_types=1);

return [

    'disks' => [
        'ftp' => [
            'driver' => 'ftp',
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),
            'root' => env('FTP_ROOT_DIRECTORY'),
        ],
        'ftp2' => [
            'driver' => 'ftp',
            'host' => env('FTP2_HOST'),
            'username' => env('FTP2_USERNAME'),
            'password' => env('FTP2_PASSWORD'),
            'root' => env('FTP2_ROOT_DIRECTORY'),
        ],
        'local' => [
            'driver' => 'local',
            'root' => base_path(),
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
        'email-attachments' => [
            'driver' => 'local',
            'root' => storage_path('app/email-attachments'),
        ],
    ],

];
