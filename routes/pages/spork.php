<?php

declare(strict_types=1);

use App\Contracts\ModelQuery;
use App\Http\Controllers;
use App\Services\Development\DescribeTableService;
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
})->name('welcome');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/api/files/{basepath}', function ($path) {
        $decoded = base64_decode($path);

        if (is_dir($decoded)) {
            $files = Storage::disk(config('spork.filesystem.default'))->files($decoded);
            $directories = Storage::disk(config('spork.filesystem.default'))->directories($decoded);

            return array_map(
                fn ($file) => [
                    'name' => basename($file),
                    'file_path' => base64_encode($file),
                    'is_directory' => is_dir($file),
                    'type' => 'file',
                ],
                array_merge(
                    $directories,
                    $files
                )
            );
        }

        return file_get_contents($decoded);
    });

    Route::get('/api/device-code', function () {
        $code = request()->user()->codes()->firstWhere('is_enabled', true);

        if (empty($code)) {
            $code = request()->user()->codes()->create([
                'short_code' => $shortCode = Str::random(),
                'long_url' => route('create-device', [
                    'short_code' => $shortCode,
                ]),
                'is_enabled' => true,
                'status' => 301
            ]);
        }

        return [
            'route' => str_replace('https://', 'http://', route('redirect', [
                'code' => $code->short_code,
            ]))
        ];
    })->name('setup-device');

    Route::get('/dashboard', Controllers\Spork\DashboardController::class)->name('dashboard');

    Route::get('finance/settings', function () {
        return Inertia::render('Banking/Settings', [

        ]);
    });

    Route::get('/projects/{project}', [Controllers\Spork\ProjectsController::class, 'show'])->name('projects.show');


    Route::get('/pages/create', [Controllers\Spork\PagesController::class, 'create'])->name('pages');

    Route::get('/servers/{server}', [Controllers\Spork\ServersController::class, 'show'])->name('servers.show');
    Route::get('/domains/{domain}', [Controllers\Spork\DomainsController::class, 'show'])->name('domains.show');

    Route::get('/user/api-query', function () {
        return Inertia::render('API/QueryBuilderPage', [
            'models' => collect(app(Filesystem::class)->allFiles(app_path('Models')))->map(function (SplFileInfo $file) {
                $modelClass = ucfirst(str_replace('/', '\\', str_replace('.php', '', substr(str_replace(base_path(), '', $file), 1))));

                return new $modelClass;
            })->filter(fn ($thing) => ($thing instanceof ModelQuery))->map(fn ($model) => $model->getTable())->values(),
        ]);
    })->middleware(\App\Http\Middleware\Authenticate::class)->name('user.api-query');

    Route::post('project/{project}/deploy', [Controllers\Spork\ProjectsController::class, 'deploy'])->name('project.deploy');

    Route::post('project/{project}/attach', [Controllers\Spork\ProjectsController::class, 'attach'])
        ->name('project.attach');

    Route::post('project/{project}/detach', [Controllers\Spork\ProjectsController::class, 'detach'])
        ->name('project.detach');
});

Route::group(['prefix' => '-', ], function () {
    Route::get('/', function () {
        $filesystem = new Illuminate\Filesystem\Filesystem();
        $env = array_filter(array_reduce(explode("\n", $filesystem->get(base_path('.env'))), function ($all, $some) {
            $envKeyValue = explode('=', $some);
            return array_merge($all, [
                $envKeyValue[0] => $envKeyValue[1] ?? null,
            ]);
        }, []));

        $allEnvValues = array_map(function (SplFileInfo $file) use ($env) {
            return [
                'env' => collect(explode("\n", file_get_contents(config_path($file->getFilename()))))
                    ->filter(fn($line) => str_contains($line, 'env('))
                    ->map(function ($line) {
                        $matches = [];
                        preg_match_all("/env\(([^)]+)\s*,\s*([^)]+)\)(,?)$/i", $line, $matches, PREG_SET_ORDER);
                        return $matches;
                    })
                    ->filter()
                    ->map(function ($matches) {
                        try {
                            [$match, $key, $default] = $matches[0];

                            $key = trim($key, '\"\'');
                            $default = trim($default, '\"\'');

                            if (trim($default) === ',') {
                                $default = null;
                            }

                            return compact('match', 'key', 'default');
                        } catch (\Throwable $e) {
                            dd($matches, $e);
                        }
                    })->reduce(function ($all, $item) use ($env) {
                        try {
                            $all[$item['key']] = json_decode($item['default'], false, 5, JSON_THROW_ON_ERROR);
                        } catch (\Throwable $e) {
                            $all[$item['key']] = $item['default'];
                        }
                        return $all;
                    }, []),
                'name' => $file->getFilename(),
            ];
        }, $filesystem->files(config_path()));

        return Inertia::render('Settings2', [
            'config' => array_filter($allEnvValues, fn($item) => !empty($item['env'])),
            'env' => $env,
        ]);
    });

    Route::get('/manage', function () {

        return Inertia::render('Manage/Index', [

        ]);
    });
    Route::get('/postal', function () {
        return Inertia::render('Postal/Index', [
            'threads' => \App\Models\Thread::query()
                ->with([
                    'participants' => function ($query) {
                        $query->where('name', 'not like', '%bridge bot%');
                    }
                ])
                ->where('updated_at', '>=', now()->subWeek(2))
                ->orderByDesc('origin_server_ts')
                ->paginate(request('limit', 15), ['*'], 'page', 1)
        ]);
    });
    Route::get('/inbox', function () {
        return Inertia::render('Postal/Inbox', [
            'threads' => (new \App\Services\ImapService)->findAllMailboxes(),
            'messages' => (new \App\Services\ImapService)->findAllFromDate('INBOX', now()->subDay())
        ]);
    });
    Route::get('/inbox/{number}', function ($messageNumber) {
        $message = (new \App\Services\ImapService)->findMessage($messageNumber, true);
        return base64_decode($message['body']);
    });
    Route::get('/postal/{thread}', function ($thread) {
        return Inertia::render('Postal/Thread', [
            'threads' => \App\Models\Thread::query()
                ->with([
                    'participants' => function ($query) {
                        $query->where('name', 'not like', '%bridge bot%');
                    }
                ])

                ->where('origin_server_ts', '>=', now()->subWeek(2))
                ->orderByDesc('origin_server_ts')
                ->paginate(request('limit', 15), ['*'], 'page', 1),
            'thread' => \App\Models\Thread::query()
                ->with(['messages' => function ($query) {
                    $query->orderBy('originated_at');
                }, 'participants' => function ($query) {
                    $query->where('name', 'not like', '%bridge bot%');
                }])
                ->orderByDesc('updated_at')
                ->findOrFail($thread),
        ]);
    });
    Route::get('/file-manager', function () {
        $filesystem = \Illuminate\Support\Facades\Storage::disk(config('spork.filesystem.default'));

        return Inertia::render('FileManager', [
            'files' => array_map(
                fn ($file) => [
                    'name' => basename($file),
                    'file_path' => base64_encode('/'.$file),
                    'is_directory' => false,
                    'type' => 'file',
                ],
                $filesystem->files()
            ),
            'directories' => array_map(
                fn ($file) => [
                    'name' => basename($file),
                    'file_path' => base64_encode('/'.$file),
                    'is_directory' => true,
                    'type' => 'folder',
                ],
                $filesystem->directories()
            ),

        ]);
    });

    Route::get('/manage/{link}', function ($link) {
        $basename = \Illuminate\Support\Str::title(Str::singular($link));
        $models = array_filter(
            array_map(fn (SplFileInfo $file) => basename($file->getBasename()), (new Filesystem)->allFiles(app_path('Models'))),
            fn ($file) => $file === $basename.'.php',
        );
        $files = array_filter(
            array_map(fn (SplFileInfo $file) => basename($file->getBasename()), (new Filesystem)->allFiles(resource_path('js/Pages/Manage'))),
            fn ($file) => str_contains($file, $basename),
        );

        abort_if(count($models) !== 1, 404);

        $file = explode('.', Arr::first($models), 2);
        $vueFile = explode('.', Arr::first($files) ?? '', 2);

        $model = 'App\\Models\\'. $file[0];

        $description = (new DescribeTableService)->describe(new $model);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $model::query()
            ->paginate(request('limit', 15), ['*'], 'page', request('page', 1));

        $data = $paginator->items();
        $paginator = $paginator->toArray();

        unset($paginator['data']);

        if (!empty($files)) {;
            return Inertia::render('Manage/'.$vueFile[0], [
                'description' => $description,
                'singular' => $file[0],
                'plural' => Str::title($link),
                'link' => $link,
                'data' => $data,
                'paginator' => $paginator
            ]);
        }

        return Inertia::render('Manage/Index', [
            'description' => $description,
            'singular' => $file[0],
            'plural' => Str::title($link),
            'link' => $link,
            'data' => $data,
            'paginator' => $paginator
        ]);
    })->where('link', '(projects|pages|people|servers|domains|tags|scripts|research|credentials|articles)')
        ->name('crud');
})->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
]);

Route::middleware([config('jetstream.auth_session'), 'verified', App\Http\Middleware\OnlyHost::class])->group(function () {
    Route::get('/admin', Controllers\AdminController::class)->name('admin');
    Route::get('/admin/email', [Controllers\AdminController::class, 'email'])->name('admin');
    Route::post('/api/install', Controllers\InstallNewProvider::class);
    Route::post('/api/uninstall', Controllers\UninstallNewProvider::class);
    Route::post('/api/enable', Controllers\EnableProviderController::class);
    Route::post('/api/disable', Controllers\DisableProviderController::class);
});
