<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts\Services;

use App\Contracts\Services\CloudflareRegistrarServiceContract;
use PHPUnit\Framework\TestCase;

class CloudflareRegistrarServiceContractTest extends TestCase
{
    public function test_contract_methods()
    {
        $contract = $this->getMockForAbstractClass(CloudflareRegistrarServiceContract::class);

        $this->assertInstanceOf(CloudflareRegistrarServiceContract::class, $contract);
    }
}
