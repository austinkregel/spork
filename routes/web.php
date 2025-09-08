<?php

declare(strict_types=1);

Route::redirect('/login', '/flight/login');

Route::domain(config('app.deploy_domain'))
    ->withoutMiddleware(['web'])
    ->group(base_path('routes/pages/deploy.php'));

Route::middleware('web')
    ->domain(config('app.location_domain'))
    ->group(base_path('routes/pages/location.php'));

Route::middleware('web')
    ->domain(config('app.spork_domain'))
    ->group(base_path('routes/pages/spork.php'));
Route::prefix('api')
    ->domain(config('app.spork_domain'))
    ->middleware(config('jetstream.middleware', ['web']))
    ->group(base_path('routes/crud.php'));

if (! empty($linkShorteningDomain = config('app.link_shortening_domain'))) {
    Route::domain($linkShorteningDomain)
        ->group(base_path('routes/pages/link-shortening.php'));
}

Route::domain(config('app.domain'))
    ->group(base_path('routes/pages/domains.php'));
