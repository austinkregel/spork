<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CreateCredentialCommand;
use App\Models\Credential;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreateCredentialCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle(): void
    {
        $command = Mockery::mock(CreateCredentialCommand::class)->makePartial();
        $command->shouldReceive('choice')
            ->once()
            ->with('What type of credential is this?', [
                Credential::TYPE_MATRIX,
                Credential::TYPE_DOMAIN,
                Credential::TYPE_EMAIL,
                Credential::TYPE_FINANCE,
                Credential::TYPE_SSH,
                Credential::TYPE_SOURCE,
                Credential::TYPE_DEVELOPMENT,
                Credential::TYPE_REGISTRAR,
                Credential::TYPE_SERVER,
            ])
            ->andReturn(Credential::TYPE_EMAIL);

        $command->shouldReceive('ask')
            ->once()
            ->with('What is the username?')
            ->andReturn('testuser');

        $command->shouldReceive('secret')
            ->once()
            ->with('What is the password?')
            ->andReturn('password');

        $this->app->instance(CreateCredentialCommand::class, $command);

        $this->artisan('make:credential')
            ->expectsOutput('Creating a new credential')
            ->expectsOutput('Credential created successfully')
            ->assertExitCode(0);

        $this->assertDatabaseHas('credentials', [
            'type' => Credential::TYPE_EMAIL,
            'username' => 'testuser',
            'password' => 'password',
        ]);
    }
}
