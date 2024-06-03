<?php

namespace App\Console\Commands;

use App\Services\Code;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;

class BulkScoutImport extends Command
{
    protected $signature = 'app:bulk-scout-import';

    protected $description = 'Command description';

    public function handle()
    {
        $searchableModels = Code::instancesOf(Searchable::class)
            ->getClasses();

        foreach($searchableModels as $model) {
            Artisan::call('scout:import', [
                'model' => $model,
            ]);
        }
    }
}
