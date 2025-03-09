<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Contracts\LogicalOperator;
use PHPUnit\Framework\TestCase;

class LogicalOperatorTest extends TestCase
{
    public function test_logical_operator_interface_exists(): void
    {
        $this->assertTrue(interface_exists(LogicalOperator::class));
    }

    public function test_logical_operator_compute_method(): void
    {
        $logicalOperator = $this->getMockForAbstractClass(LogicalOperator::class);

        $this->assertTrue(
            method_exists($logicalOperator, 'compute'),
            'Class does not have method compute'
        );
    }
}
