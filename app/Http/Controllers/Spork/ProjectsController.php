<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Projects\ProjectTemplates;
use App\Services\Development\DescribeTableService;
use App\Services\Projects\ProjectAttachmentService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectsController extends Controller
{
    public function index()
    {
        $model = \App\Models\Project::class;
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = auth()->user()->personalProjects()
            ->paginate(request('limit', 15), ['*'], 'page', request('page', 1));

        $data = $paginator->items();
        $paginator = $paginator->toArray();

        unset($paginator['data']);

        return Inertia::render('Projects/Index', [
            'data' => $data,
            'paginator' => $paginator,
        ]);
    }

    public function show(Project $project)
    {
        $project->load([
            'pages.domain',
            'research',
            'credentials',
            'servers.tags',
            'domains',
            'budgets',
            'accounts',
            'transactions',
            'externalRssFeeds',
            'people',
            'threads',
            'automations',
            'tasks',
            'deployments.domain',
            'deployments.server',
            'deployments.domains',
            'deployments.servers',
        ]);

        return Inertia::render('Projects/Project', [
            'project' => $project,
        ]);
    }

    public function deploy(Project $project)
    {
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

    public function attach(Request $request, Project $project, ProjectAttachmentService $attachments)
    {
        $data = $request->validate([
            'resource_type' => ['required', 'string'],
            'resource_id' => ['required', 'integer'],
        ]);

        $attachments->attach(
            project: $project,
            resource_type: $data['resource_type'],
            resource_id: (int) $data['resource_id'],
        );

        return response()->json([], 204);
    }

    public function detach(Request $request, Project $project, ProjectAttachmentService $attachments)
    {
        $data = $request->validate([
            'resource_type' => ['required', 'string'],
            'resource_id' => ['required', 'integer'],
        ]);

        $attachments->detach(
            project: $project,
            resource_type: $data['resource_type'],
            resource_id: (int) $data['resource_id'],
        );

        return response()->json([], 204);
    }

    public function create(
        DescribeTableService $description_service,
        ProjectTemplates $templates,
    ) {
        $description = $description_service->describe(new Project);

        return Inertia::render('Projects/Create', [
            'description' => $description,
            'project_templates' => $templates->forFrontend(),
        ]);
    }

    public function store(
        StoreProjectRequest $request,
        ConnectionInterface $db,
        ProjectAttachmentService $attachment_service,
        ProjectTemplates $templates,
    ) {
        $data = $request->validated();

        $attachments = $data['attachments'] ?? [];
        unset($data['attachments']);

        $data['settings'] = $this->normalizeSettings($data['settings'] ?? null);
        if (is_array($data['settings'] ?? null) && array_key_exists('template', $data['settings'])) {
            $data['settings']['template'] = $templates->normalizeKey((string) $data['settings']['template']);
        }

        /** @var Project $project */
        $project = null;

        $db->transaction(function () use (&$project, $data, $attachments, $attachment_service): void {
            $project = new Project;
            $project->forceFill($data);
            $project->save();

            foreach ($attachments as $attachment) {
                $attachment_service->attach(
                    project: $project,
                    resource_type: (string) ($attachment['resource_type'] ?? ''),
                    resource_id: (int) ($attachment['resource_id'] ?? 0),
                );
            }
        });

        return redirect()->route('projects.show', $project);
    }

    private function normalizeSettings(mixed $settings): ?array
    {
        if ($settings === null) {
            return null;
        }

        if (is_array($settings)) {
            return $settings;
        }

        if (is_string($settings) && trim($settings) === '') {
            return null;
        }

        if (is_string($settings)) {
            $decoded = json_decode($settings, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
