<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Research;
use Inertia\Inertia;

class ResearchController
{
    public function index()
    {
        return Inertia::render('Research/Dashboard', [
            'research' => request()->user()->projects()
                ->with('research')
                ->get()
                ->map(fn ($project) => $project->research)
                ->flatten(),
        ]);
    }

    public function show(Research $research)
    {
        return Inertia::render('Research/Topic', [
            'topic' => $research,
        ]);
    }
}
