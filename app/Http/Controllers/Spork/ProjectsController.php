<?php

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectsController extends Controller
{
    public function index()
    {
        return Inertia::render('Projects/Projects', []);
    }

    public function show(Project $project) {
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
    }

    public function deploy(Project $project) {
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

    }

    public function attach(Project $project) {
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
    }

    public function detach(Project $project) {
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
    }
}
