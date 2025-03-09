<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use PHPUnit\Framework\TestCase;
use App\Contracts\ActionInterface;

class ActionInterfaceTest extends TestCase
{
    public function testActionInterfaceMethods()
    {
        $this->assertTrue(interface_exists(ActionInterface::class));
    }
}
