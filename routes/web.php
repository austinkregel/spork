<?php

declare(strict_types=1);

if (file_exists(base_path('routes/generate-pages.php'))) {
    include_once base_path('routes/generate-pages.php');
}
Route::domain('b8b6-24-231-174-107.ngrok-free.app')
//    ->middleware('throttle:5')
    ->withoutMiddleware(['web'])
    ->group(base_path('routes/pages/deploy.php'));

Route::prefix('api')
    ->domain(config('app.env') == 'production' ? 'spork.zone' : 'spork.localhost')
    ->middleware(config('jetstream.middleware', ['web']))
    ->group(base_path('routes/crud.php'));

Route::middleware('web')
    ->domain(config('app.env') == 'production' ? 'petoskey.today' : 'petoskey.localhost')
    ->group(base_path('routes/pages/petoskey.php'));

Route::middleware('web')
    ->domain(config('app.env') == 'production' ? 'spork.zone' : 'spork.localhost')
    ->group(base_path('routes/pages/spork.php'));

if (!empty($linkShorteningDomain = env('LINK_SHORTENING_DOMAIN', ''))) {
    Route::domain($linkShorteningDomain)
        ->group(base_path('routes/pages/link-shortening.php'));
}
