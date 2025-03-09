<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts\Services;

use App\Contracts\Services\CloudflareDomainServiceContract;
use PHPUnit\Framework\TestCase;

class CloudflareDomainServiceContractTest extends TestCase
{
    public function testHasEmailRouting(): void
    {
        $mock = $this->createMock(CloudflareDomainServiceContract::class);
        $mock->expects($this->once())
            ->method('hasEmailRouting')
            ->with('example.com')
            ->willReturn(true);

        $this->assertTrue($mock->hasEmailRouting('example.com'));
    }
}
