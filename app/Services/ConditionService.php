<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Conditionable;
use App\Models\Condition;
use App\Services\Condition\AbstractLogicalOperator;
use App\Services\Condition\ArrayContainsValueOperator;
use App\Services\Condition\ContainsValueOperator;
use App\Services\Condition\ContainsValueStrictOperator;
use App\Services\Condition\DoesntContainValueOperator;
use App\Services\Condition\DoesntEqualValueOperator;
use App\Services\Condition\EndsWithOperator;
use App\Services\Condition\EqualsValueOperator;
use App\Services\Condition\GreaterThanOperator;
use App\Services\Condition\GreaterThanOrEqualToOperator;
use App\Services\Condition\HasRoleOperator;
use App\Services\Condition\LessThanOperator;
use App\Services\Condition\LessThanOrEqualToOperator;
use App\Services\Condition\StartsWithOperator;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

class ConditionService
{
    public const AVAILABLE_CONDITIONS = [
        // strings, numbers, arrays, etc..
        Condition::COMPARATOR_NOT_EQUAL => DoesntEqualValueOperator::class,
        Condition::COMPARATOR_EQUALS => EqualsValueOperator::class,

        // *strings* or arrays,
        Condition::COMPARATOR_IN => ArrayContainsValueOperator::class,
        Condition::COMPARATOR_LIKE => ContainsValueOperator::class,
        Condition::COMPARATOR_LIKE_STRICT => ContainsValueStrictOperator::class,
        Condition::COMPARATOR_NOT_LIKE => DoesntContainValueOperator::class,

        // Numbers
        Condition::COMPARATOR_GREATER_THAN => GreaterThanOperator::class,
        Condition::COMPARATOR_GREATER_THAN_EQUAL => GreaterThanOrEqualToOperator::class,
        Condition::COMPARATOR_LESS_THAN => LessThanOperator::class,
        Condition::COMPARATOR_LESS_THAN_EQUAL => LessThanOrEqualToOperator::class,

        // Strings
        Condition::COMPARATOR_STARTS_WITH => StartsWithOperator::class,
        Condition::COMPARATOR_ENDS_WITH => EndsWithOperator::class,

        'HAS_ROLE' => HasRoleOperator::class,
    ];

    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function process(Conditionable $item, array $additionalValueData = []): bool
    {
        if ($item->conditions->count() === 0) {
            return true;
        }

        $returnedValue = true;
        /** @var Condition $condition */
        foreach ($item->conditions as $condition) {
            $comparator = static::AVAILABLE_CONDITIONS[$condition->comparator];
            /** @var AbstractLogicalOperator $instance */
            $instance = new $comparator;

            $passesCondition = $instance->compute(
                // Looking for the condition value
                $condition->value,
                // inside the parameter's interpolated value.
                $value = $this->processParameter($condition->parameter, $additionalValueData),
            );

            if ($passesCondition && ! $item->must_all_conditions_pass) {
                $this->logCondition($condition, $passesCondition, $value);

                return true;
            }

            if (! $passesCondition) {
                $returnedValue = false;
            }
        }
        $this->logCondition($condition, $passesCondition, $value);

        return $returnedValue;
    }

    protected function logCondition(Condition $condition, bool $passesCondition, $value)
    {
        $this->logger->info("Condition: Is [$value] {$condition->parameter} {$condition->comparator} {$condition->value}", [
            'passes_condition' => $passesCondition,
            'value' => $value,
        ]);
    }

    protected function processParameter(string $parameter, array $additionalData)
    {
        // This might be some thing like config:app.env to return a config value
        if (str_contains($parameter, ':')) {
            [$primaryKey, $field] = explode(':', $parameter);

            $valueExtractionFunction = $this->matchCustomPrimaryKeyFunctions($primaryKey, $field);

            return $valueExtractionFunction($field);
        }

        if ($parameter === 'user') {
            return auth()->user() ?? null;
        }

        // This can be an single dimensions, or a multidimensional array
        // Access via dot notation.
        return Arr::get($additionalData, $parameter);
    }

    protected function matchCustomPrimaryKeyFunctions(string $key, ?string $parameter)
    {
        return match ($key) {
            'config' => fn ($field) => config($field),
            default => dd($key, $parameter),
        };
    }
}
