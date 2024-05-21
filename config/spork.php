<?php

declare(strict_types=1);

return [
    'prefix' => '',
    'filesystem' => [
        'default' => env('SPORK_DEFAULT_FILESYSTEM'),
    ],
    'code' => [
        'enabled' => true,
        'settings' => [
            // These vendors dont always match 100% with the versions or available interfaces, likely due to missing dev dependencies.
            'blacklist' => [
                'nesbot',
                'lesstif',
                'doctrine',
                'google',
                'psy',
                'cboden',
                'symfony',
                'phpunit',
                'mockery',
                'zendframework',
                'nativephp',
                'laravel',
            ],
            'whitelist' => [],
        ],
    ],
];
