<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Operations;

use App\Console\Commands\Operations\MakeOperationMigrationCommand;
use App\Console\Commands\Operations\MigrationCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class MakeOperationMigrationCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = $this->mock(MigrationCreator::class);
        $this->composer = $this->mock(\Illuminate\Support\Composer::class);

        $this->app->instance(MigrationCreator::class, $this->creator);
        $this->app->instance(\Illuminate\Support\Composer::class, $this->composer);
    }

    public function test_handle_creates_migration(): void
    {
        $this->creator->shouldReceive('create')
            ->once()
            ->with('create_test_operations_table', database_path('migrations'), 'test_operations', true)
            ->andReturn('database/migrations/'.date('Y_m_d_His').'_create_test_operations_table.php');

        $this->artisan('make:operation-migration', ['name' => 'TestOperation'])
            ->expectsOutput('Created Migration: database/migrations/'.date('Y_m_d_His').'_create_test_operations_table.php')
            ->assertExitCode(0);
    }
}
