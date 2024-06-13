<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Inertia\Inertia;

class ServersController extends Controller
{
    public function index()
    {
        $servers = auth()->user()->servers()->paginate(10);

        return Inertia::render('Servers/Index', [
            'servers' => $servers->items(),
            'pagination' => $servers,
        ]);
    }

    public function show(Server $server)
    {
        $server->load([
            'tags',
            'credential',
            'projects',
            'services'
        ]);

        return Inertia::render('Servers/Show', [
            'server' => $server,
        ]);
    }
}
