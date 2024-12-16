<?php

declare(strict_types=1);

use Laravel\Pennant\Feature;

if (Feature::driver('array')->active(\App\Features\Automatic\GeneratedPages::class)) {
    if (file_exists(base_path('routes/generate-pages.php'))) {
        include_once base_path('routes/generate-pages.php');
    }
}

if (Feature::driver('array')->active(\App\Features\Automatic\ServerLinking::class)) {
    Route::domain('pending.download')
//    ->middleware('throttle:5')
        ->withoutMiddleware(['web'])
        ->group(base_path('routes/pages/deploy.php'));
}

if (Feature::driver('array')->active(\App\Features\Automatic\Crud::class)) {
    Route::prefix('api')
        ->domain(config('app.env') == 'production' ? 'spork.zone' : 'spork.localhost')
        ->middleware(config('jetstream.middleware', ['web']))
        ->group(base_path('routes/crud.php'));
}

if (Feature::driver('array')->active(\App\Features\PetoskeyToday::class)) {
    Route::middleware('web')
        ->domain(config('app.env') == 'production' ? 'petoskey.today' : 'petoskey.localhost')
        ->group(base_path('routes/pages/petoskey.php'));
}

if (Feature::driver('array')->active(\App\Features\SporkApp::class)) {
    Route::middleware('web')
        ->domain(config('app.env') == 'production' ? 'spork.zone' : 'spork.localhost')
        ->group(base_path('routes/pages/spork.php'));
}

if (Feature::driver('array')->active(\App\Features\LinkShortening::class)) {
    if (!empty($linkShorteningDomain = env('LINK_SHORTENING_DOMAIN', ''))) {
        Route::domain($linkShorteningDomain)
            ->group(base_path('routes/pages/link-shortening.php'));
    }
}

if (Feature::driver('array')->active(\App\Features\Domains::class)) {
    Route::domain(
        config('app.env') == 'production'
            ? 'starting.host'
            : 'domains.localhost'
    )
        ->group(base_path('routes/pages/domains.php'));
}
