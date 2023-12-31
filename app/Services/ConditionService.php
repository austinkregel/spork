<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Conditionable;
use App\Models\Condition;
use App\Models\Navigation;
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

class ConditionService
{
    public const AVAILABLE_CONDITIONS = [
        // strings, numbers, arrays, etc..
        Condition::COMPARATOR_NOT_EQUAL => DoesntEqualValueOperator::class,
        Condition::COMPARATOR_EQUALS => EqualsValueOperator::class,

        // *strings* or arrays,
        Condition::COMPARATOR_IN => ContainsValueOperator::class,
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

    public function navigation()
    {
        // So we want to filter out any nav items
        $navItems = Navigation::query()
            ->with('conditions')
            ->where('authentication_required', auth()->check())
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(function (Navigation $item) {
                $item->current = $item->href === request()->getRequestUri() || ($item->children->isNotEmpty() && $item->children->filter(fn ($item) => $item->href === request()->getRequestUri())->count() > 0);

                return $item;
            });

        return $navItems->filter(function (Navigation $item) {
            return $this->process($item);
        });
    }

    public function process(Conditionable $item)
    {
        if ($item->conditions->count() === 0) {
            return true;
        }

        return $item->conditions->filter(function (Condition $condition) {
            $comparator = static::AVAILABLE_CONDITIONS[$condition->comparator];
            /** @var ContainsValueOperator $instance */
            $instance = new $comparator;

            return $instance->compute($this->processParameter($condition->parameter), $condition->value);
        })->count() === $item->conditions->count();
    }

    protected function processParameter(string $parameter)
    {
        if (str_contains($parameter, ':')) {
            [$primaryKey, $field] = explode(':', $parameter);

            $valueExtractionFunction = $this->matchCustomPrimaryKeyFunctions($primaryKey, $field);

            return $valueExtractionFunction($field);
        }

        if ($parameter === 'user') {
            return auth()->user();
        }

        dd('this is likely a parameter, not a function', $parameter);
    }

    protected function matchCustomPrimaryKeyFunctions(string $key, ?string $parameter)
    {
        return match ($key) {
            'config' => fn ($field) => config($field),
            default => dd($key, $parameter),
        };
    }
}
