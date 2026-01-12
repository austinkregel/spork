<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\StepContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ConditionStepHandler
{
    protected array $comparatorMap = [
        'EQUALS' => 'equals',
        'NOT_EQUAL' => 'notEquals',
        'LIKE' => 'like',
        'NOTLIKE' => 'notLike',
        'STARTS_WITH' => 'startsWith',
        'ENDS_WITH' => 'endsWith',
        'IN' => 'in',
        'NOTIN' => 'notIn',
        'GREATER_THAN' => 'greaterThan',
        'GREATER_THAN_EQUAL' => 'greaterThanEqual',
        'LESS_THAN' => 'lessThan',
        'LESS_THAN_EQUAL' => 'lessThanEqual',
    ];

    /**
     * @return array{output?:string,error?:string,skip?:bool,data?:array}
     */
    public function execute(Automation $automation, AutomationStep $step, StepContext $context): array
    {
        $config = $step->config ?? [];
        $conditions = $config['conditions'] ?? [];
        $onFalse = $config['on_false'] ?? 'skip';
        $source = $config['source'] ?? 'globals';

        if (empty($conditions)) {
            return ['error' => 'Condition step requires conditions'];
        }

        $dataset = $this->resolveDataset($context, $source);

        if (empty($dataset)) {
            return $this->handleFailure($onFalse);
        }

        $filtered = array_values(array_filter($dataset, fn ($item) => $this->itemPasses($item, $conditions)));

        if (empty($filtered)) {
            return $this->handleFailure($onFalse);
        }

        return [
            'output' => sprintf('Conditions passed for %d item(s)', count($filtered)),
            'data' => $filtered,
        ];
    }

    protected function resolveDataset(StepContext $context, string $source): array
    {
        if ($source === 'globals') {
            return [$context->globals()];
        }

        if ($source === 'previous') {
            return $context->latestData();
        }

        if (Str::startsWith($source, 'step:')) {
            $index = (int) Str::after($source, 'step:');

            return $context->dataFor($index);
        }

        return [];
    }

    protected function handleFailure(string $onFalse): array
    {
        if ($onFalse === 'fail') {
            return ['error' => 'Conditions failed'];
        }

        return [
            'output' => 'Conditions failed; skipping remaining steps',
            'skip' => true,
            'data' => [],
        ];
    }

    protected function itemPasses(array $item, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $parameter = $condition['parameter'] ?? null;
            $comparator = strtoupper((string) ($condition['comparator'] ?? ''));

            if (! $parameter || ! isset($this->comparatorMap[$comparator])) {
                return false;
            }

            $actual = Arr::get($item, $parameter);
            $expected = $condition['value'] ?? null;
            $method = $this->comparatorMap[$comparator];

            if (! $this->{$method}($actual, $expected)) {
                return false;
            }
        }

        return true;
    }

    protected function equals($actual, $expected): bool
    {
        return (string) $actual === (string) $expected;
    }

    protected function notEquals($actual, $expected): bool
    {
        return ! $this->equals($actual, $expected);
    }

    protected function like($actual, $expected): bool
    {
        return Str::contains(Str::lower((string) $actual), Str::lower((string) $expected));
    }

    protected function notLike($actual, $expected): bool
    {
        return ! $this->like($actual, $expected);
    }

    protected function startsWith($actual, $expected): bool
    {
        return Str::startsWith(Str::lower((string) $actual), Str::lower((string) $expected));
    }

    protected function endsWith($actual, $expected): bool
    {
        return Str::endsWith(Str::lower((string) $actual), Str::lower((string) $expected));
    }

    protected function in($actual, $expected): bool
    {
        $values = Arr::wrap($expected);

        return in_array($actual, $values, true);
    }

    protected function notIn($actual, $expected): bool
    {
        return ! $this->in($actual, $expected);
    }

    protected function greaterThan($actual, $expected): bool
    {
        return (float) $actual > (float) $expected;
    }

    protected function greaterThanEqual($actual, $expected): bool
    {
        return (float) $actual >= (float) $expected;
    }

    protected function lessThan($actual, $expected): bool
    {
        return (float) $actual < (float) $expected;
    }

    protected function lessThanEqual($actual, $expected): bool
    {
        return (float) $actual <= (float) $expected;
    }
}
