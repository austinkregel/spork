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
            'files' => collect((new \Illuminate\Filesystem\Filesystem())->directories(app_path()))
                ->map(fn ($directory) => [
                    'name' => basename($directory),
                    'file_path' => base64_encode($directory),
                    'is_directory' => true,
                ])
                ->concat(
                    collect((new \Illuminate\Filesystem\Filesystem())->files(app_path()))
                        ->map(fn (\SplFileInfo $file) => [
                            'name' => $file->getFilename(),
                            'file_path' => base64_encode($file->getPathname()),
                            'is_directory' => false,
                        ])
                ),

        ]);
    }
}
