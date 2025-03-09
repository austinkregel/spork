<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Contracts\LogicalEvent;
use PHPUnit\Framework\TestCase;

class LogicalEventTest extends TestCase
{
    public function test_logical_event_interface_exists(): void
    {
        $this->assertTrue(interface_exists(LogicalEvent::class));
    }
}
