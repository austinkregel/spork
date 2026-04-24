<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Code;

use App\Services\Code;
use Tests\TestCase;

class CodeInstancesOfTest extends TestCase
{
    public function test_instances_of_returns_empty_array_for_unknown_parent(): void
    {
        $code = Code::instancesOf('This\\Class\\Does\\Not\\Exist');

        $this->assertIsArray($code->getClasses());
        $this->assertSame([], $code->getClasses());
    }
}
