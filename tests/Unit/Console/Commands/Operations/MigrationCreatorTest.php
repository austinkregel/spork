<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Operations;

use App\Console\Commands\Operations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrationCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_migration(): void
    {
        $filesystem = new Filesystem();
        $creator = new MigrationCreator($filesystem, __DIR__.'/../../stubs');

        $migrationPath = $creator->create('create_test_table', database_path('migrations'));

        $this->assertFileExists($migrationPath);
        $this->assertStringContainsString('create_test_table', $migrationPath);

        // Clean up
        unlink($migrationPath);
    }
}
