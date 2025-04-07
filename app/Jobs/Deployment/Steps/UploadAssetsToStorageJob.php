<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UploadAssetsToStorageJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Server $server,
        public Domain $domain,
        public Project $project
    ) {}

    public function handle(): void
    {

        // Get the path to the compiled assets and vendor directory
        $compiledAssetsPath = public_path('dist');
        $vendorPath = base_path('vendor');

        // Create a temporary zip file
        $zipPath = sys_get_temp_dir().'/assets.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Add the compiled assets to the zip file
        $compiledAssetsFiles = Storage::files($compiledAssetsPath);
        foreach ($compiledAssetsFiles as $file) {
            $zip->addFile(public_path($file), 'assets/'.$file);
        }

        // Add the vendor files to the zip file
        $vendorFiles = Storage::allFiles($vendorPath);
        foreach ($vendorFiles as $file) {
            $zip->addFile(base_path($file), 'vendor/'.$file);
        }

        // Close the zip file
        $zip->close();

        // Upload the zip file to the S3 storage
        $s3Path = 'assets/assets.zip'; // Replace 'assets/assets.zip' with your desired S3 path
        Storage::disk('s3')->put($s3Path, file_get_contents($zipPath));

        // Delete the temporary zip file
        unlink($zipPath);
    }
}
