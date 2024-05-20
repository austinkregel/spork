<?php

if (file_exists(base_path('routes/generate-pages.php'))) {
    include_once base_path('routes/generate-pages.php');
}

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

Route::middleware('api')
    ->domain(config('app.env') == 'production' ? 'deploy.kregel.host' : 'deploy.localhost')
    ->group(base_path('routes/pages/deploy.php'));

Route::domain(env('LINK_SHORTENING_DOMAIN', ''))
    ->group(base_path('routes/pages/link-shortening.php'));
