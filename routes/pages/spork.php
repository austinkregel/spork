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
        return Inertia::render('Dashboard', [
            'project_count' => \App\Models\Project::count(),
            'server_count' => \App\Models\Server::count(),
            'domain_count' => \App\Models\Domain::count(),
            'credential_count' => \App\Models\Credential::count(),
            'user_count' => \App\Models\User::count(),
            'activity_logs' => \Spatie\Activitylog\Models\Activity::query()
                ->with('causer')
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        ]);
    })->name('dashboard');

    Route::get('/projects', function () {
        return Inertia::render('Projects/Projects', []);
    })->name('projects');

    Route::get('/projects/{project}', function (App\Models\Project $project) {
        $project->load([
            'servers.tags',
            'domains.records',
            'pages.domain',
            'research',
            'credentials',
            'domains' => function ($domainQuery) {
                $domainQuery->with([
                    'domainAnalytics' => function ($analyticsQuery) {
                        $analyticsQuery
                            ->select([
                                \DB::raw('sum(query_count) as query_count'),
                                \DB::raw('sum(uncached_count) as uncached_count'),
                                \DB::raw('sum(stale_count) as stale_count'),
                                \DB::raw('min(date) as min_date'),
                                \DB::raw('max(date) as max_date'),
                                'domain_id',
                            ])
                            ->where('date', '>=', now()->subHours(24))
                            ->where('date', '<=', now())
                            ->groupBy('domain_id', DB::raw('date(date)'))
                            ->orderBy('query_count', 'desc');
                    },
                ]);
            },
        ]);

        return Inertia::render('Projects/Project', [
            'project' => $project,
            'project_analytics' => [
                [
                    'name' => 'Total Queries',
                    'stat' => $project->domains->reduce(fn ($carry, $domain) => $carry + $domain->domainAnalytics->sum('query_count'), 0),
                    'duration' => $project->domains->reduce(function (int $carry, $domain) {
                        $maxDate = $domain->domainAnalytics->map->min_date->min();
                        $minDate = $domain->domainAnalytics->map->max_date->max();

                        return max(\Carbon\Carbon::parse($maxDate)->diffInHours(\Carbon\Carbon::parse($minDate)), $carry);
                    }, 0).' hours',
                ],
                [
                    'name' => 'Total Uncached',
                    'stat' => $project->domains->reduce(fn ($carry, $domain) => $carry + $domain->domainAnalytics->sum('uncached_count'), 0),
                    'duration' => $project->domains->reduce(function (int $carry, $domain) {
                        $maxDate = $domain->domainAnalytics->map->min_date->min();
                        $minDate = $domain->domainAnalytics->map->max_date->max();

                        return max(\Carbon\Carbon::parse($maxDate)->diffInHours(\Carbon\Carbon::parse($minDate)), $carry);
                    }, 0).' hours',
                ],
                [
                    'name' => 'Total Stale',
                    'stat' => $project->domains->reduce(fn ($carry, $domain) => $carry + $domain->domainAnalytics->sum('stale_count'), 0),
                    'duration' => $project->domains->reduce(function (int $carry, $domain) {
                        $maxDate = $domain->domainAnalytics->map->min_date->min();
                        $minDate = $domain->domainAnalytics->map->max_date->max();

                        return max(\Carbon\Carbon::parse($maxDate)->diffInHours(\Carbon\Carbon::parse($minDate)), $carry);
                    }, 0).' hours',

                ],
            ],
        ]);
    })->name('projects.show');

    Route::get('/pages', function () {
        return Inertia::render('Pages', []);
    })->name('pages');

    Route::get('/pages/create', function () {
        return Inertia::render('Pages/Create', []);
    })->name('pages');

    Route::get('/servers', function () {
        return Inertia::render('Servers', []);
    })->name('servers');
    Route::get('/credentials', function () {
        return Inertia::render('Credentials', []);
    })->name('credentials');
    Route::get('/servers/{server}', function (App\Models\Server $project) {
        return Inertia::render('Servers', [
            'project' => $project,
        ]);
    })->name('servers.show');

    Route::get('/domains', function () {
        return Inertia::render('Domains', []);
    })->name('domains');
    Route::get('/domains/{domain}', function (App\Models\Domain $domain) {
        return Inertia::render('Domains', [
            'domain' => $domain,
        ]);
    })->name('domains.show');

    Route::get('/user/api-query', function () {
        return Inertia::render('API/QueryBuilderPage', [
            'models' => collect(app(Filesystem::class)->allFiles(app_path('Models')))->map(function (SplFileInfo $file) {
                $modelClass = ucfirst(str_replace('/', '\\', str_replace('.php', '', substr(str_replace(base_path(), '', $file), 1))));

                return new $modelClass;
            })->filter(fn ($thing) => ($thing instanceof ModelQuery))->map(fn ($model) => $model->getTable())->values(),
        ]);
    })->middleware(\App\Http\Middleware\Authenticate::class);

    Route::post('project/{project}/deploy', function (App\Models\Project $project) {
        $project->load([
            'servers.tags', 'domains',
        ]);

        $forgeCredential = $project->credentials()->where('service', 'forge')->first();
        $cloudflareCredential = $project->credentials()->where('service', 'cloudflare')->first();
        $namecheapCredential = $project->credentials()->where('service', 'namecheap')->first();

        /** @var \App\Models\Server $server */
        foreach ($project->servers as $server) {
            $tags = array_map(fn ($tag) => $tag->name->en, $server->tags);
            if (in_array('loadbalancer', $tags)) {
                // Link the load balancer's network to all the other servers, only add servers that are labeled `web`
            }
            foreach ($project->domains as $domain) {
                if (in_array('loadbalancer', $tags)) {
                    // Each one of these jobs should look to see if the configuration is already where we want it.
                    dispatch_sync(new \App\Jobs\Deployment\Steps\SetupCloudflareDns($domain, $cloudflareCredential, $namecheapCredential));
                    dispatch_sync(new \App\Jobs\Deployment\Steps\SetupLoadBalancerJob($server, $domain, $project));
                    dispatch_sync(new \App\Jobs\Deployment\Steps\SetupLoadBalancerDnsRecordJob($server, $domain, $project));
                    dispatch_sync(new \App\Jobs\Deployment\Steps\DeploySslCertificateJob($server, $domain, $forgeCredential));
                }
                if (in_array('web', $tags)) {
                    // Setup domain on server
                    // Setup project for server (setup git, setup deployment webhook, etc etc...)
                    // Update the environment variables with share values.
                    // Configure jobs/queues for server
                    // configure cron schedules/daemons
                }
                // Basically a queue worker, or a project with a prod env that isn't _the_ prod server.
                if (in_array('app', $tags)) {
                    // Queue workers are not setup to handle traffic from the load balancer
                    // Setup domain on server
                    // Setup project for server (setup git, setup deployment webhook, etc etc...)
                    // Update the environment variables with share values.
                    // Configure jobs/queues for server
                    // configure cron schedules/daemons
                }
            }
        }

    });
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

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->post('project/{project}/attach', function (App\Models\Project $project) {
    //    request()
    request()->validate([
        'resource_type' => \Illuminate\Validation\Rule::in([
            \App\Models\Server::class,
            \App\Models\Domain::class,
            \App\Models\Credential::class,
            \App\Models\Page::class,
        ]),
    ]);

    if (\DB::table('project_resources')->where([
        'resource_type' => request()->get('resource_type'),
        'resource_id' => request()->get('resource_id'),
        'project_id' => $project->id,
    ])->exists()) {
        return response([
            'message' => 'Already exists',
        ], 422);
    }

    \DB::table('project_resources')->insert([
        'resource_type' => request()->get('resource_type'),
        'resource_id' => request()->get('resource_id'),
        'project_id' => $project->id,
        'settings' => '{}',
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->post('project/{project}/detach', function (App\Models\Project $project) {
    //    request()
    request()->validate([
        'resource_type' => \Illuminate\Validation\Rule::in([
            \App\Models\Server::class,
            \App\Models\Domain::class,
            \App\Models\Credential::class,
            \App\Models\Page::class,
        ]),
    ]);

    \DB::table('project_resources')->where([
        'resource_type' => request()->get('resource_type'),
        'resource_id' => request()->get('resource_id'),
        'project_id' => $project->id,
    ])->delete();
});
