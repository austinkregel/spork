<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Services\Code;
use Illuminate\Notifications\Notification;
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
            'notifications' =>
                array_map(
                    fn ($class) => $class,
                    Code::instancesOf(Notification::class)->getClasses(),
                )

        ]);
    }
}
