<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Operations;

use App\Console\Commands\Operations\MakeOperationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeOperationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_operation(): void
    {
        $this->artisan('make:operation', ['name' => 'TestOperation'])
            ->expectsOutput('Operation created successfully.')
            ->assertExitCode(0);

        $this->assertFileExists(app_path('Operations/TestOperation.php'));
    }

    public function test_handle_creates_migration_if_option_is_provided(): void
    {
        $this->artisan('make:operation', ['name' => 'TestOperation', '--migration' => true])
            ->expectsOutput('Operation created successfully.')
            ->expectsOutput('Migration created successfully.')
            ->assertExitCode(0);

        $this->assertFileExists(app_path('Operations/TestOperation.php'));
        $this->assertFileExists(database_path('migrations/'.date('Y_m_d_His').'_create_test_operations_table.php'));
    }
}
