<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Actions\Fortify\CreateNewUser;
use App\Console\Commands\MakeUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeUserTest extends TestCase
{
    public function test_handle(): void
    {
        $command = $this->getMockBuilder(MakeUser::class)
            ->onlyMethods(['ask'])
            ->getMock();

        $command->expects($this->exactly(3))
            ->method('ask')
            ->willReturnOnConsecutiveCalls('John Doe', 'john@example.com', 'password');

        $action = $this->getMockBuilder(CreateNewUser::class)
            ->onlyMethods(['create'])
            ->getMock();

        $action->expects($this->once())
            ->method('create')
            ->with([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => true,
            ]);

        $command->handle();
    }
}
