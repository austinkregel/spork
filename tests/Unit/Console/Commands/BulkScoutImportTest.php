<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\BulkScoutImport;
use App\Services\Code;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\Searchable;
use Mockery;
use Tests\TestCase;

class BulkScoutImportTest extends TestCase
{
    public function test_handle(): void
    {
        $searchableModel = Mockery::mock(Searchable::class);
        $searchableModel->shouldReceive('getClasses')
            ->andReturn(['App\\Models\\ExampleModel']);

        $codeService = Mockery::mock(Code::class);
        $codeService->shouldReceive('instancesOf')
            ->with(Searchable::class)
            ->andReturn($searchableModel);

        $this->app->instance(Code::class, $codeService);

        Artisan::shouldReceive('call')
            ->once()
            ->with('scout:import', ['model' => 'App\\Models\\ExampleModel'], Mockery::any());

        $command = new BulkScoutImport();
        $command->handle();
    }
}
