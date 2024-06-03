<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use Inertia\Inertia;

class FileManagerController
{
    public function __invoke()
    {
        $filesystem = \Illuminate\Support\Facades\Storage::disk(config('spork.filesystem.default'));

        return Inertia::render('FileManager', [
            'files' => array_map(
                fn ($file) => [
                    'name' => basename($file),
                    'file_path' => base64_encode('/'.$file),
                    'is_directory' => false,
                    'type' => 'file',
                    'last_modified' => \Carbon\Carbon::parse($filesystem->lastModified($file)),
                ],
                $filesystem->files()
            ),
            'directories' => array_map(
                fn ($file) => [
                    'name' => basename($file),
                    'file_path' => base64_encode('/'.$file),
                    'is_directory' => true,
                    'type' => 'folder',
                    'last_modified' => \Carbon\Carbon::parse($filesystem->lastModified($file)),
                ],
                $filesystem->directories()
            ),

        ]);
    }

    public function show($path)
    {
        $decoded = base64_decode($path);

        if (is_dir($decoded)) {
            return collect((new \Illuminate\Filesystem\Filesystem())->directories($decoded))
                ->map(fn ($directory) => [
                    'name' => basename($directory),
                    'file_path' => base64_encode($directory),
                    'is_directory' => true,
                ])
                ->concat(
                    collect((new \Illuminate\Filesystem\Filesystem())->files($decoded))
                        ->map(fn (\SplFileInfo $file) => [
                            'name' => $file->getFilename(),
                            'file_path' => base64_encode($file->getPathname()),
                            'is_directory' => false,
                        ])
                );
        }

        return file_get_contents($decoded);
    }
}
