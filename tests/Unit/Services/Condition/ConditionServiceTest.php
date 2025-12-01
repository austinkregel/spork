<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Condition;

use App\Models\Condition;
use App\Services\ConditionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConditionServiceTest extends TestCase
{
    public function test_match_custom_primary_key_functions_throws_for_unknown_key(): void
    {
        $service = new ConditionService(new NullLogger());

        $reflection = new \ReflectionMethod(ConditionService::class, 'matchCustomPrimaryKeyFunctions');
        $reflection->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);

        $closure = $reflection->invoke($service, 'unknown', 'param');
        $closure('field');
    }
}


