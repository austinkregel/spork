<?php

declare(strict_types=1);

use App\Features;

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

    'features' => [
        Features\SporkApp::class => true,
        Features\LinkShortening::class => true,
        Features\PetoskeyToday::class => true,
        Features\Websockets::class => true,
        Features\Automatic\Crud::class => true,
        Features\Automatic\GeneratedPages::class => true,
        Features\Automatic\ServerLinking::class => true,
    ],
];
