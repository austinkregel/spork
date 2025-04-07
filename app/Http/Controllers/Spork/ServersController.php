<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Server;
use Inertia\Inertia;

class ServersController extends Controller
{
    public function index()
    {
        $servers = auth()->user()
            ->servers()
            ->with('tags', 'services')
            ->paginate(10);

        return Inertia::render('Infrastructure/Index', [
            'servers' => $servers->items(),
            'pagination' => $servers,
            'sshCredential' => auth()->user()->credentials()->firstWhere('type', Credential::TYPE_SSH),
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
