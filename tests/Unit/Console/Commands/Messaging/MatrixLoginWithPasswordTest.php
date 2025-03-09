<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Messaging;

use App\Console\Commands\Messaging\MatrixLoginWithPassword;
use App\Models\Credential;
use App\Services\Messaging\MatrixClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MatrixLoginWithPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle(): void
    {
        $username = 'testuser';
        $password = 'password';
        $host = 'matrix.org';

        $matrixClientMock = Mockery::mock(MatrixClient::class);
        $matrixClientMock->shouldReceive('loginWithPassword')
            ->with($username, $password)
            ->once()
            ->andReturn([
                'access_token' => 'mocked_access_token',
                'device_id' => 'mocked_device_id',
                'home_server' => 'mocked_home_server',
                'well_known' => [
                    'm.homeserver' => [
                        'base_url' => 'mocked_base_url',
                    ],
                ],
            ]);

        $this->app->instance(MatrixClient::class, $matrixClientMock);

        $command = Mockery::mock(MatrixLoginWithPassword::class.'[argument,option,secret]', [
            'argument' => $username,
            'option' => $host,
        ]);

        $command->shouldReceive('argument')
            ->with('username')
            ->andReturn($username);
        $command->shouldReceive('option')
            ->with('host')
            ->andReturn($host);
        $command->shouldReceive('secret')
            ->with('What is the password?')
            ->andReturn($password);

        $command->handle(new Credential());

        $this->assertDatabaseHas('credentials', [
            'access_token' => 'mocked_access_token',
            'settings->device_id' => 'mocked_device_id',
            'settings->home_server' => 'mocked_home_server',
            'settings->matrix_server' => 'mocked_base_url',
        ]);
    }
}
