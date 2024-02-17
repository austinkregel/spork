<?php

declare(strict_types=1);

use App\Actions\Spork\CustomAction;
use App\Contracts\ModelQuery;
use App\Http\Controllers;
use App\Models\Person;
use App\Services\Development\DescribeTableService;
use App\Services\Programming\LaravelProgrammingStyle;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
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
    $instances = LaravelProgrammingStyle::instancesOf(CustomAction::class);

    foreach ($instances->constructorProperty('slug') as $file => $classAndSlug) {
        foreach ($classAndSlug as $class => $slugWithQuote) {
            Route::post('/api/actions/'.trim($slugWithQuote, '\''), $class);
        }
    }

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
                'status' => 301,
            ]);
        }

        return [
            'route' => str_replace('https://', 'http://', route('redirect', [
                'code' => $code->short_code,
            ])),
        ];
    })->name('setup-device');

    Route::post('/api/mail/mark-as-read', Controllers\Api\Mail\MarkAsReadController::class);
    Route::post('/api/mail/mark-as-unread', Controllers\Api\Mail\MarkAsUnreadController::class);
    Route::post('/api/mail/mark-as-spam', Controllers\Api\Mail\MarkAsSpamAndMoveController::class);
    Route::post('/api/mail/reply', Controllers\Api\Mail\ReplyController::class);
    Route::post('/api/mail/reply-all', Controllers\Api\Mail\ReplyAllController::class);
    Route::post('/api/mail/forward', Controllers\Api\Mail\ForwardMessageController::class);
    Route::post('/api/mail/destroy', Controllers\Api\Mail\DestroyMailController::class);

    Route::post('/api/plaid/create-link-token', Controllers\Api\Plaid\CreateLinkTokenController::class);
    Route::post('/api/plaid/exchange-token', Controllers\Api\Plaid\ExchangeTokenController::class);

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

Route::group(['prefix' => '-', 'middleware' => [
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
]], function () {
    Route::get('/dashboard', function () {
        $person = Person::whereJsonContains('emails', auth()->user()->email)
            // for now, this is fine, my email base does support this idea, but I know if someone/
            // wanted to be malicious they could take advantage of this.
            ->first();

        return Inertia::render('Dashboard', [
            'project_count' => \App\Models\Project::count(),
            'server_count' => \App\Models\Server::count(),
            'domain_count' => \App\Models\Domain::count(),
            'credential_count' => \App\Models\Credential::count(),
            'user_count' => \App\Models\User::count(),
            // Unread Messages
            // Tasks due today
            // Domains that expire this month, or in the last 7 days
            // Weather at my primary address
            'weather' => Arr::first(app(\App\Contracts\Services\WeatherServiceContract::class)->query(
                $person->primary_address,
            )),
            'unread_messages' => request()->user()
                ->messages()
                ->count(),
            'messages' => request()->user()
                ->messages()
                ->paginate(15, ['*'], 'messages_page'),

        ]);
    });

    Route::get('/tag-manager', function () {
        return Inertia::render('Tags/Index', [
            'tags' => \App\Models\Tag::withCount([
                'conditions',
                'articles',
                'feeds',
                'servers',
                'transactions',
                'projects',
                'budgets',
                'accounts',
                'domains',
                'people',
                'messages' => function ($q) {
                    $q->where('seen', false);
                },
            ])
                ->with(['conditions'])
                ->orderBy('type')
                ->paginate(
                    request('limit', 30),
                    ['*'],
                    'page',
                    request('page')
                ),
        ]);
    });
    Route::get('/postal', function () {
        return Inertia::render('Postal/Index', [
            'threads' => \App\Models\Thread::query()
                ->with([
                    'participants' => function ($query) {
                        $query->where('name', 'not like', '%bridge bot%');
                    },
                ])
                ->orderByDesc('origin_server_ts')
                ->paginate(request('limit', 15), ['*'], 'page', 1),
        ]);
    });
    Route::get('/projects', function () {
        $model = \App\Models\Project::class;

        $description = (new DescribeTableService)->describe(new $model);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $model::query()
            ->paginate(request('limit', 15), ['*'], 'page', request('page', 1));

        $data = $paginator->items();
        $paginator = $paginator->toArray();

        unset($paginator['data']);

        return Inertia::render('Manage/Index', [
            'title' => 'CRUD '.Str::ucfirst(str_replace('_', ' ', Str::ascii((new $model)->getTable(), 'en'))),
            'description' => $description,
            'singular' => Str::singular((new $model)->getTable()),
            'plural' => Str::title((new $model)->getTable()),
            'link' => '/'.(new $model)->getTable(),
            'apiLink' => '/api/crud/'.(new $model)->getTable(),
            'data' => $data,
            'paginator' => $paginator,
        ]);
    });
    Route::get('/research', function () {
        return Inertia::render('Research/Dashboard', [
            'research' => \App\Models\Research::all(),
        ]);
    });
    Route::get('/research/{research}', function (App\Models\Research $research) {
        return Inertia::render('Research/Topic', [
            'topic' => $research,
        ]);
    });
    Route::get('/inbox', function () {
        return Inertia::render('Postal/Inbox', [
            'messages' => \App\Models\Message::query()
                ->with('from', 'to')
                ->where('type', 'email')
                ->orderByDesc('originated_at')
                ->paginate(),
        ]);
    });
    Route::get('/inbox/{message}', function (App\Models\Message $message) {
        abort_if($message->type !== 'email', 404);

        $message = (new \App\Services\ImapService)->findMessage($message->event_id, true);
        $messageBody = base64_decode($message['body']);

        $bodyWithTheImagesDisabledForPrivacy = str_replace(' src=', ' data-src=', $messageBody);

        return view('emails.'.$message['view'], [
            'body' => $bodyWithTheImagesDisabledForPrivacy,
        ]);
    });
    Route::get('/postal/{thread}', function ($thread) {
        return Inertia::render('Postal/Thread', [
            'threads' => \App\Models\Thread::query()
                ->with([
                    'participants' => function ($query) {
                        $query->where('name', 'not like', '%bridge bot%');
                    },
                ])

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
                    'last_modified' => \Carbon\Carbon::parse($filesystem->lastModified($file)),
                ],
                $filesystem->files()
            ),
            'directories' => array_map(
                fn ($file) => [
                    'name' => basename($file),
                    'file_path' => base64_encode('/'.$file),
                    'is_directory' => true,
                    'type' => 'folder',
                    'last_modified' => \Carbon\Carbon::parse($filesystem->lastModified($file)),
                ],
                $filesystem->directories()
            ),

        ]);
    });

    Route::get('/projects/{project}', [Controllers\Spork\ProjectsController::class, 'show'])->name('projects.show');

    Route::get('/manage', function () {
        return Inertia::render('Manage/Index', [
            'title' => 'Dynamic CRUD',
            'description' => [
                'fillable' => [],
            ],
        ]);
    });
    Route::get('/banking', function () {
        $accounts = request()->user()
            ->accounts()
            ->with('credential')->get();

        return Inertia::render('Finance/Index', [
            'title' => 'Banking ',
            'accounts' => $accounts,
            'transactions' => \App\Models\Finance\Transaction::whereIn('account_id', $accounts->pluck('account_id'))
                ->with('tags')
                ->orderByDesc('date')
                ->paginate(),
        ]);
    });
    Route::get('/manage/{link}', function ($model) {
        $description = (new DescribeTableService)->describe(new $model);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $model::query()
            ->paginate(request('limit', 15), ['*'], 'page', request('page', 1));

        $data = $paginator->items();
        $paginator = $paginator->toArray();

        unset($paginator['data']);

        return Inertia::render('Manage/Index', [
            'title' => 'CRUD '.Str::ucfirst(str_replace('_', ' ', Str::ascii((new $model)->getTable(), 'en'))),
            'description' => $description,
            'singular' => Str::singular((new $model)->getTable()),
            'plural' => Str::title((new $model)->getTable()),
            'link' => '/'.(new $model)->getTable(),
            'apiLink' => '/api/crud/'.(new $model)->getTable(),
            'data' => $data,
            'paginator' => $paginator,
        ]);
    })->name('crud');
    Route::get('/settings', function () {
        // settings are things that can be configured in between requests.
        // They cannot be changed at run time, and might even require a restart of the servers.
        return Inertia::render('Settings/Index', [
            'title' => 'Settings',
            'settings' => new class()
            {
            },
        ]);
    });
});

Route::middleware([
    'web',
    config('jetstream.auth_session'),
    'verified',
    App\Http\Middleware\OnlyHost::class,
    \App\Http\Middleware\OnlyInDevelopment::class,
])->group(function () {
    Route::post('/api/install', Controllers\InstallNewProvider::class);
    Route::post('/api/uninstall', Controllers\UninstallNewProvider::class);
    Route::post('/api/enable', Controllers\EnableProviderController::class);
    Route::post('/api/disable', Controllers\DisableProviderController::class);

    Route::get('/-/logic', function () {
        return Inertia::render('Logic/Index', [
            'container_bindings' => \App\Services\Programming\LaravelProgrammingStyle::findContainerBindings(),
            'events' => \App\Services\Programming\LaravelProgrammingStyle::findLogicalEvents(),
            'listeners' => \App\Services\Programming\LaravelProgrammingStyle::findLogicalListeners(),
        ]);
    });
    Route::post('/api/logic/add-listener-for-event', Controllers\Logic\AddListenerForEventController::class);
    Route::post('/api/logic/remove-listener-for-event', Controllers\Logic\RemoveListenerForEventController::class);

});
