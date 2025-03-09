<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Operations;

use App\Console\Commands\Operations\QueueCommand;
use App\Operations\Operator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QueueCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_queues_operations(): void
    {
        $operatorMock = Mockery::mock('alias:'.Operator::class);
        $operatorMock->shouldReceive('queue')->once();

        $command = new QueueCommand();
        $command->handle();
    }
}
