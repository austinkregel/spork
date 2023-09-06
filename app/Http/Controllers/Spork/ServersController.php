<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Inertia\Inertia;

class ServersController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Servers', []);
    }

    public function show(Server $project)
    {
        return Inertia::render('Servers', [
            'project' => $project,
        ]);
    }
}
