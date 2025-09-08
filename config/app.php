<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Facade;

return [

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
        'Operator' => App\Operations\Operator::class,
    ])->toArray(),

    /**
     * This is the primary application
     */
    'spork_domain' => env('SPORK_DOMAIN', 'spork.localhost'),

    /**
     * This provides a way to display weather, and articles with a specific tag
     */
    'location_domain' => env('LOCATION_DOMAIN', 'location.localhost'),
    'location_tag' => env('LOCATION_TAG', 'Petoskey'),
    'location_name' => env('LOCATION_NAME', 'Petoskey, MI'),

    /**
     * Deploy domain; related to linking servers for infrastructure management
     */
    'deploy_domain' => env('DEPLOY_DOMAIN', 'deploy.localhost'),

    /**
     * A very simple link shortening service
     */
    'link_shortening_domain' => env('LINK_SHORTENING_DOMAIN', 'link.localhost'),
];
