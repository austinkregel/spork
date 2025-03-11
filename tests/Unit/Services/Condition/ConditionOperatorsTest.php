<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Condition;

use App\Services\Condition\ArrayContainsValueOperator;
use App\Services\Condition\ContainsValueOperator;
use App\Services\Condition\ContainsValueStrictOperator;
use App\Services\Condition\DoesntContainValueOperator;
use App\Services\Condition\DoesntEqualValueOperator;
use App\Services\Condition\EndsWithOperator;
use App\Services\Condition\EqualsValueOperator;
use App\Services\Condition\FilterIn;
use App\Services\Condition\GreaterThanOperator;
use App\Services\Condition\GreaterThanOrEqualToOperator;
use App\Services\Condition\HasRoleOperator;
use App\Services\Condition\LessThanOperator;
use App\Services\Condition\LessThanOrEqualToOperator;
use App\Services\Condition\StartsWithOperator;
use PHPUnit\Framework\TestCase;

class ConditionOperatorsTest extends TestCase
{
    const EXPECT_TO_PASS = true;
    const EXPECT_TO_FAIL = false;

    /**
     * @dataProvider conditionOperatorsProvider
     */
    public function testConditionOperators($operator, $needle, $haystack, $expected)
    {
        $this->assertEquals($expected, $operator->compute($needle, $haystack));
    }

    public function conditionOperatorsProvider()
    {
        return [
            [new ArrayContainsValueOperator(), 'Netflix', ['Netflix', 'Spotify'], self::EXPECT_TO_PASS],
            [new ArrayContainsValueOperator(), 'Hulu', ['Netflix', 'Spotify'], self::EXPECT_TO_FAIL],
            [new ArrayContainsValueOperator(), 'Netflix', 'Netflix,Spotify', self::EXPECT_TO_PASS],
            [new ArrayContainsValueOperator(), 'Hulu', 'Netflix,Spotify', self::EXPECT_TO_FAIL],

            [new ContainsValueOperator(), 'Netflix', ['Netflix', 'Spotify'], self::EXPECT_TO_PASS],
            [new ContainsValueOperator(), 'Hulu', ['Netflix', 'Spotify'], self::EXPECT_TO_FAIL],
            [new ContainsValueOperator(), 'Netflix', 'Netflix Spotify', self::EXPECT_TO_PASS],
            [new ContainsValueOperator(), 'Hulu', 'Netflix Spotify', self::EXPECT_TO_FAIL],
            [new ContainsValueOperator(), 'Netflix', (object)['Netflix' => 'test'], self::EXPECT_TO_PASS],
            [new ContainsValueOperator(), 'Hulu', (object)['Netflix' => 'test'], self::EXPECT_TO_FAIL],

            [new ContainsValueStrictOperator(), 'Netflix', ['Netflix', 'Spotify'], self::EXPECT_TO_PASS],
            [new ContainsValueStrictOperator(), 'Hulu', ['Netflix', 'Spotify'], self::EXPECT_TO_FAIL],
            [new ContainsValueStrictOperator(), 'Netflix', 'Netflix Spotify', self::EXPECT_TO_PASS],
            [new ContainsValueStrictOperator(), 'Hulu', 'Netflix Spotify', self::EXPECT_TO_FAIL],
            [new ContainsValueStrictOperator(), 'Netflix', (object)['Netflix' => 'test'], self::EXPECT_TO_PASS],
            [new ContainsValueStrictOperator(), 'Hulu', (object)['Netflix' => 'test'], self::EXPECT_TO_FAIL],
            [new ContainsValueStrictOperator(), 'Netflix', 'Netflix', self::EXPECT_TO_PASS],
            [new ContainsValueStrictOperator(), 'Netflix', 'Spotify', self::EXPECT_TO_FAIL],

            [new DoesntContainValueOperator(), 'Netflix', ['Netflix', 'Spotify'], self::EXPECT_TO_FAIL],
            [new DoesntContainValueOperator(), 'Hulu', ['Netflix', 'Spotify'], self::EXPECT_TO_PASS],
            [new DoesntContainValueOperator(), 'Netflix', 'Netflix Spotify', self::EXPECT_TO_FAIL],
            [new DoesntContainValueOperator(), 'Hulu', 'Netflix Spotify', self::EXPECT_TO_PASS],
            [new DoesntContainValueOperator(), 'Netflix', (object)['Netflix' => 'test'], self::EXPECT_TO_FAIL],
            [new DoesntContainValueOperator(), 'Hulu', (object)['Netflix' => 'test'], self::EXPECT_TO_PASS],

            [new DoesntEqualValueOperator(), 'Netflix', 'Netflix', self::EXPECT_TO_FAIL],
            [new DoesntEqualValueOperator(), 'Netflix', 'Spotify', self::EXPECT_TO_PASS],
            [new DoesntEqualValueOperator(), 'Netflix', null, self::EXPECT_TO_PASS],
            [new DoesntEqualValueOperator(), null, 'Netflix', self::EXPECT_TO_PASS],
            [new DoesntEqualValueOperator(), null, null, self::EXPECT_TO_FAIL],

            [new EndsWithOperator(), 'Netflix', 'SuperNetflix', self::EXPECT_TO_PASS],
            [new EndsWithOperator(), 'Netflix', 'SuperUserNetflix', self::EXPECT_TO_PASS],
            [new EndsWithOperator(), 'Netflix', 'SuperUserNetfli', self::EXPECT_TO_FAIL],
            [new EndsWithOperator(), 'Netflix', 'Netflix', self::EXPECT_TO_PASS],
            [new EndsWithOperator(), 'Netflix', 'Spotify', self::EXPECT_TO_FAIL],

            [new EqualsValueOperator(), 'Netflix', 'Netflix', self::EXPECT_TO_PASS],
            [new EqualsValueOperator(), 'Netflix', 'Spotify', self::EXPECT_TO_FAIL],
            [new EqualsValueOperator(), 'Netflix', null, self::EXPECT_TO_FAIL],
            [new EqualsValueOperator(), null, 'Netflix', self::EXPECT_TO_FAIL],
            [new EqualsValueOperator(), null, null, self::EXPECT_TO_PASS],

            [new FilterIn(), 'Netflix', ['Netflix', 'Spotify'], self::EXPECT_TO_PASS],
            [new FilterIn(), 'Hulu', ['Netflix', 'Spotify'], self::EXPECT_TO_FAIL],
            [new FilterIn(), 'Netflix', 'Netflix Spotify', self::EXPECT_TO_PASS],
            [new FilterIn(), 'Hulu', 'Netflix Spotify', self::EXPECT_TO_FAIL],
            [new FilterIn(), 'Netflix', (object)['Netflix' => 'test'], self::EXPECT_TO_PASS],
            [new FilterIn(), 'Hulu', (object)['Netflix' => 'test'], self::EXPECT_TO_FAIL],

            [new GreaterThanOperator(), 50, 30, self::EXPECT_TO_PASS],
            [new GreaterThanOperator(), 30, 50, self::EXPECT_TO_FAIL],
            [new GreaterThanOperator(), 50, 50, self::EXPECT_TO_FAIL],
            [new GreaterThanOperator(), '50', '30', self::EXPECT_TO_PASS],
            [new GreaterThanOperator(), '30', '50', self::EXPECT_TO_FAIL],
            [new GreaterThanOperator(), '50', '50', self::EXPECT_TO_FAIL],

            [new GreaterThanOrEqualToOperator(), 50, 30, self::EXPECT_TO_PASS],
            [new GreaterThanOrEqualToOperator(), 30, 50, self::EXPECT_TO_FAIL],
            [new GreaterThanOrEqualToOperator(), 50, 50, self::EXPECT_TO_PASS],
            [new GreaterThanOrEqualToOperator(), '50', '30', self::EXPECT_TO_PASS],
            [new GreaterThanOrEqualToOperator(), '30', '50', self::EXPECT_TO_FAIL],
            [new GreaterThanOrEqualToOperator(), '50', '50', self::EXPECT_TO_PASS],

            [new HasRoleOperator(), 'Netflix', ['Netflix', 'Spotify'], self::EXPECT_TO_PASS],
            [new HasRoleOperator(), 'Spotify', ['Netflix', 'Spotify'], self::EXPECT_TO_PASS],
            [new HasRoleOperator(), 'Hulu', ['Netflix', 'Spotify'], self::EXPECT_TO_FAIL],
            [new HasRoleOperator(), 'Netflix', 'Netflix,Spotify', self::EXPECT_TO_PASS],
            [new HasRoleOperator(), 'Hulu', 'Netflix,Spotify', self::EXPECT_TO_FAIL],

            [new LessThanOperator(), 30, 50, self::EXPECT_TO_PASS],
            [new LessThanOperator(), 50, 30, self::EXPECT_TO_FAIL],
            [new LessThanOperator(), 50, 50, self::EXPECT_TO_FAIL],
            [new LessThanOperator(), '30', '50', self::EXPECT_TO_PASS],
            [new LessThanOperator(), '50', '30', self::EXPECT_TO_FAIL],
            [new LessThanOperator(), '50', '50', self::EXPECT_TO_FAIL],

            [new LessThanOrEqualToOperator(), 30, 50, self::EXPECT_TO_PASS],
            [new LessThanOrEqualToOperator(), 50, 30, self::EXPECT_TO_FAIL],
            [new LessThanOrEqualToOperator(), 50, 50, self::EXPECT_TO_PASS],
            [new LessThanOrEqualToOperator(), '30', '50', self::EXPECT_TO_PASS],
            [new LessThanOrEqualToOperator(), '50', '30', self::EXPECT_TO_FAIL],
            [new LessThanOrEqualToOperator(), '50', '50', self::EXPECT_TO_PASS],

            [new StartsWithOperator(), 'Netflix', 'Netflix Spotify', self::EXPECT_TO_PASS],
            [new StartsWithOperator(), 'Netflix', 'Spotify Netflix', self::EXPECT_TO_FAIL],
            [new StartsWithOperator(), 'Netflix', 'Netflix', self::EXPECT_TO_PASS],
            [new StartsWithOperator(), 'Netflix', 'Spotify', self::EXPECT_TO_FAIL],
        ];
    }
}
