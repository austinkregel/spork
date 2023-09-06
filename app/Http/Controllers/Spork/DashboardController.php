<?php

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Dashboard', [
            'project_count' => \App\Models\Project::count(),
            'server_count' => \App\Models\Server::count(),
            'domain_count' => \App\Models\Domain::count(),
            'credential_count' => \App\Models\Credential::count(),
            'user_count' => \App\Models\User::count(),
            'activity_logs' => \Spatie\Activitylog\Models\Activity::query()
                ->with('causer')
                ->orderBy('created_at', 'desc')
                ->paginate(20),
        ]);
    }
}
