<?php

declare(strict_types=1);

use App\Contracts\ModelQuery;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\Finder\SplFileInfo;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/projects', function () {
        return Inertia::render('Projects/Projects', []);
    })->middleware('auth:sanctum')->name('projects');

    Route::get('/projects/{project}', function (App\Models\Project $project) {
        $project->load([
            'servers.tags',
            'domains.records',
            'pages',
            'research',
            'domains' => function ($domainQuery) {
                $domainQuery->with([
                    'domainAnalytics' => function ($analyticsQuery) {
                        $analyticsQuery
                            ->select([
                                \DB::raw('sum(query_count) as query_count'),
                                \DB::raw('sum(uncached_count) as uncached_count'),
                                \DB::raw('sum(stale_count) as stale_count'),
                                'domain_id',
                            ])
                            ->where('date', '>=', now()->startOfDay())
                            ->where('date', '<=', now())
                            ->groupBy('domain_id')
                            ->orderBy('query_count', 'desc');
                    },
                ]);
            },
        ]);

        return Inertia::render('Projects/Project', [
            'project' => $project,
        ]);
    })->middleware('auth:sanctum')->name('projects.show');

    Route::get('/pages', function () {
        return Inertia::render('Pages', []);
    })->middleware('auth:sanctum')->name('pages');

    Route::get('/servers', function () {
        return Inertia::render('Servers', []);
    })->middleware('auth:sanctum')->name('servers');
    Route::get('/credentials', function () {
        return Inertia::render('Credentials', []);
    })->middleware('auth:sanctum')->name('credentials');
    Route::get('/servers/{server}', function (App\Models\Server $project) {
        return Inertia::render('Servers', [
            'project' => $project,
        ]);
    })->middleware('auth:sanctum')->name('servers.show');

    Route::get('/domains', function () {
        return Inertia::render('Domains', []);
    })->middleware('auth:sanctum')->name('domains');
    Route::get('/domains/{domain}', function (App\Models\Domain $domain) {
        return Inertia::render('Domains', [
            'domain' => $domain,
        ]);
    })->middleware('auth:sanctum')->name('domains.show');

    Route::get('/user/api-query', function () {
        return Inertia::render('API/QueryBuilderPage', [
            'models' => collect(app(Filesystem::class)->allFiles(app_path('Models')))->map(function (SplFileInfo $file) {
                $modelClass = ucfirst(str_replace('/', '\\', str_replace('.php', '', substr(str_replace(base_path(), '', $file), 1))));

                return new $modelClass;
            })->filter(fn ($thing) => ($thing instanceof ModelQuery))->map(fn ($model) => $model->getTable())->values(),
        ]);
    })->middleware(\App\Http\Middleware\Authenticate::class);
});
