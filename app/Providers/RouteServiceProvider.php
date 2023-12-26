<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Code;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        Route::macro('domains', function (array $domains, $callback) {
            foreach ($domains as $domain) {
                Route::domain($domain)->group($callback)->name($domain);
            }
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        Route::bind('abstract_model', function ($tableName) {
            return collect(app(Filesystem::class)->allFiles(app_path('Models')))->filter(fn (SplFileInfo $file) => ! str_contains(strtolower($file->getRealPath()), 'trait'))->map(function (SplFileInfo $file) {
                $modelClass = ucfirst(str_replace('/', '\\', str_replace('.php', '', substr(str_replace(base_path(), '', $file->getRealPath()), 1))));

                return new $modelClass;
            })->filter(function ($model) use ($tableName) {
                return $model->getTable() === $tableName;
            })->first();
        });

        Route::bind('abstract_model_id', function ($value) {
            $class = request()->route('abstract_model');

            $model = new $class;

            return $model::find($value);
        });

        Route::bind('link', function ($value) {
            $model = Arr::first(array_values(array_filter(
                Code::instancesOf(Model::class)
                    ->getClasses(),
                function ($class) use ($value) {
                    try {
                        return (new $class)->getTable() === Str::slug($value, '_');
                    } catch (\Throwable $e) {
                        return false;
                    }
                })));

            abort_unless(isset($model), 404);

            return $model;
        });
        $this->routes(function () {
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

            Route::domain(env('LINK_SHORTENING_DOMAIN'))
                ->group(base_path('routes/pages/link-shortening.php'));
        });
    }
}
