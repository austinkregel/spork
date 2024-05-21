<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use Inertia\Inertia;

class SettingsController
{
    public function __invoke()
    {
        // settings are things that can be configured in between requests.
        // They cannot be changed at run time, and might even require a restart of the servers.
        return Inertia::render('Settings/Index', [
            'title' => 'Settings',
            'settings' => new class()
            {
            },
            'notifications' => auth()->user()->notifications,

        ]);
    }
}
