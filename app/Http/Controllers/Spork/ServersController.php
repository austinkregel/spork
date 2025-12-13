<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Server;
use App\Services\Infrastructure\InfrastructureOverviewService;
use Inertia\Inertia;

class ServersController extends Controller
{
    public function __construct(
        private readonly InfrastructureOverviewService $overviewService,
    ) {
    }

    public function index()
    {
        $overview = $this->overviewService->build(auth()->user());

        return Inertia::render('Infrastructure/Index', array_merge($overview, [
            'sshCredential' => auth()->user()->credentials()->firstWhere('type', Credential::TYPE_SSH),
            'quickActions' => [],
        ]));
    }

    public function create()
    {
        $overview = $this->overviewService->build(auth()->user());
        $sshKeys = auth()->user()->credentials()
            ->where('type', Credential::TYPE_SSH)
            ->get()
            ->map(fn (Credential $credential) => [
                'id' => $credential->id,
                'name' => $credential->name,
                'resolved_key_id' => $credential->settings['digital_ocean_key_id']
                    ?? $credential->settings['key_id']
                    ?? $credential->settings['droplet_key_id']
                    ?? null,
            ])
            ->map(function ($key) {
                $key['resolved_key_id'] = $key['resolved_key_id'] ?? (string) $key['id'];

                return $key;
            })
            ->values();

        return Inertia::render('Infrastructure/Create', [
            'providers' => $overview['providers'],
            'domains' => $overview['domains'],
            'sshKeys' => $sshKeys,
        ]);
    }

    public function show(Server $server)
    {
        $server->load([
            'tags',
            'credential',
            'projects',
            'services',
        ]);

        return Inertia::render('Infrastructure/Show', [
            'server' => $server,
        ]);
    }

    public function console(Server $server)
    {
        $server->load([
            'tags',
            'credential',
            'projects',
            'services',
        ]);

        return Inertia::render('Infrastructure/Server/SshConsole', [
            'server' => $server,
        ]);
    }

    public function keys(Server $server)
    {
        $server->load([
            'tags',
            'credential',
            'projects',
            'services',
        ]);

        return Inertia::render('Infrastructure/Show', [
            'server' => $server,
        ]);
    }

    public function workers(Server $server)
    {
        $server->load([
            'tags',
            'credential',
            'projects',
            'services',
        ]);

        return Inertia::render('Infrastructure/Show', [
            'server' => $server,
        ]);
    }

    public function crontab(Server $server)
    {
        $server->load([
            'tags',
            'credential',
            'projects',
            'services',
        ]);

        return Inertia::render('Infrastructure/Show', [
            'server' => $server,
        ]);
    }

    public function logs(Server $server)
    {
        $server->load([
            'tags',
            'credential',
            'projects',
            'services',
        ]);

        return Inertia::render('Infrastructure/Show', [
            'server' => $server,
        ]);
    }
}
