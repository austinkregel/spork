<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Contracts\Conditionable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PHPUnit\Framework\TestCase;

class ConditionableTest extends TestCase
{
    public function test_conditions_method_exists()
    {
        $conditionable = $this->getMockForAbstractClass(Conditionable::class);

        $this->assertTrue(
            method_exists($conditionable, 'conditions'),
            'Class does not have method conditions'
        );
    }

    public function test_conditions_method_returns_morph_many()
    {
        $conditionable = $this->getMockForAbstractClass(Conditionable::class);

        $this->assertInstanceOf(
            MorphMany::class,
            $conditionable->conditions()
        );
    }
}
