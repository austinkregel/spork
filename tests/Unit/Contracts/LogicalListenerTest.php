<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Contracts\LogicalListener;
use PHPUnit\Framework\TestCase;

class LogicalListenerTest extends TestCase
{
    public function test_logical_listener_interface_exists(): void
    {
        $this->assertTrue(interface_exists(LogicalListener::class));
    }
}
