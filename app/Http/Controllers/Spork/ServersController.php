<?php

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServersController extends Controller
{
    public function index()
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
