<?php

declare(strict_types=1);

use App\Contracts\ModelQuery;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\Finder\SplFileInfo;
use App\Http\Controllers;

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
    Route::get('/dashboard', Controllers\Spork\DashboardController::class)->name('dashboard');

    Route::get('/projects', [Controllers\Spork\ProjectsController::class, 'index'])->name('projects');

    Route::get('/projects/{project}', [Controllers\Spork\ProjectsController::class, 'show'])->name('projects.show');

    Route::get('/pages', Controllers\Spork\PagesController::class)->name('pages');

    Route::get('/pages/create', [Controllers\Spork\PagesController::class, 'create'])->name('pages');

    Route::get('/people', Controllers\Spork\PeopleController::class)->name('people');

    Route::get('/servers', Controllers\Spork\ServersController::class)->name('servers');
    Route::get('/credentials', Controllers\Spork\CredentialsController::class)->name('credentials');
    Route::get('/servers/{server}', [Controllers\Spork\ServersController::class])->name('servers.show');

    Route::get('/domains', Controllers\Spork\DomainsController::class)->name('domains');
    Route::get('/domains/{domain}', )->name('domains.show');

    Route::get('/user/api-query', function () {
        return Inertia::render('API/QueryBuilderPage', [
            'models' => collect(app(Filesystem::class)->allFiles(app_path('Models')))->map(function (SplFileInfo $file) {
                $modelClass = ucfirst(str_replace('/', '\\', str_replace('.php', '', substr(str_replace(base_path(), '', $file), 1))));

                return new $modelClass;
            })->filter(fn ($thing) => ($thing instanceof ModelQuery))->map(fn ($model) => $model->getTable())->values(),
        ]);
    })->middleware(\App\Http\Middleware\Authenticate::class);

    Route::post('project/{project}/deploy', [Controllers\Spork\ProjectsController::class, 'deploy']);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->post('register-device', function () {
    $credential = \App\Models\Credential::find(3);

    return \App\Models\Server::create(array_merge(request()->all(), [
        'server_id' => mt_rand(4, 192000),
        'disk' => (float) request()->get('disk'),
        'memory' => (int) request()->get('memory'),
        'last_ping_at' => \Carbon\Carbon::parse(request()->get('last_ping_at')),
    ]));
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])
    ->post('project/{project}/attach', [Controllers\Spork\ProjectsController::class, 'attach']);

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->post('project/{project}/detach', [Controllers\Spork\ProjectsController::class, 'detach']);
