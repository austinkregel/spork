<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Messaging;

use App\Console\Commands\Messaging\MatrixBeeperRequestCode;
use App\Services\Messaging\MatrixClient;
use Illuminate\Console\Command;
use Mockery;
use Tests\TestCase;

class MatrixBeeperRequestCodeTest extends TestCase
{
    public function test_handle(): void
    {
        $email = 'test@example.com';
        $host = 'beeper.com';

        $matrixClientMock = Mockery::mock(MatrixClient::class);
        $matrixClientMock->shouldReceive('requestCodeForBeeper')
            ->with($email)
            ->once();

        $this->app->instance(MatrixClient::class, $matrixClientMock);

        $command = Mockery::mock(MatrixBeeperRequestCode::class.'[argument,option,info,warn]', [
            'argument' => $email,
            'option' => $host,
        ]);

        $command->shouldReceive('argument')
            ->with('email')
            ->andReturn($email);
        $command->shouldReceive('option')
            ->with('host')
            ->andReturn($host);
        $command->shouldReceive('info')
            ->times(2);
        $command->shouldReceive('warn')
            ->once();

        $command->handle();
    }
}
