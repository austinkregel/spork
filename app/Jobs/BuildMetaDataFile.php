<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;

class BuildMetaDataFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $path
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $process = new Process([
            '/usr/bin/mediainfo',
            '--Output=JSON',
            '--Full',
            $this->path,
        ]);
        $process->run(function ($e, $o) {
            $file = new \SplFileInfo($this->path);
            // Will only be executed on supported types.
            $name = $file->getBasename();
            $split = explode('.', $name);
            $extension = end($split);
            $path = $file->getPathname();

            $data = json_decode($o, true);

            $data['name'] = $name;
            $data['path'] = $path;
            // bytes
            $data['size'] = $file->getSize();
            $data['type'] = $file->getType();

            if (! file_exists(storage_path('meta/'.$extension))) {
                (new Filesystem)
                    ->makeDirectory(storage_path('meta/'.$extension), 0755, true);
            }

            $metadataNewPath = storage_path('meta/'.$extension.'/'.md5($path).'.json');
            $metadataPath = storage_path('meta/'.md5($path).'.json');

            if (file_exists($metadataPath)) {
                // info('Danger, file conflict in md5 hash', $data);
                (new Filesystem)->move($metadataPath, $metadataNewPath);

                return;
            }

            file_put_contents($metadataNewPath, json_encode($data, JSON_PRETTY_PRINT));
        });
    }
}
