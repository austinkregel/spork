<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Jobs\OperationJob;
use App\Models\Automation;
use App\Models\AutomationStep;
use App\Operations\Operation;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class OperationStepHandler
{
    /**
     * @return array{output?:string,error?:string,data?:array}
     */
    public function execute(Automation $automation, AutomationStep $step): array
    {
        $config = $step->config ?? [];
        $operationClass = $config['operation'] ?? null;

        if (! is_string($operationClass) || $operationClass === '') {
            return ['error' => 'Operation step requires an operation class'];
        }

        if (! class_exists($operationClass) || ! is_subclass_of($operationClass, Operation::class)) {
            return ['error' => 'Provided class is not a valid Operation'];
        }

        /** @var class-string<Operation> $operationClass */
        /** @var Operation $operation */
        $operation = new $operationClass;
        $attributes = $this->normalizeAttributes($config['attributes'] ?? []);

        $operation->fill(array_merge($attributes, [
            'should_run_at' => Carbon::now(),
        ]));
        $operation->save();

        if (! empty($config['queue'])) {
            $operation->queue = (string) $config['queue'];
        }

        $operation->started_run_at = Carbon::now();
        $operation->save();

        if (method_exists($operation, 'queue')) {
            $operation->queue();
        }

        (new OperationJob($operation))->handle();
        $operation->refresh();

        return [
            'output' => sprintf(
                'Operation %s executed (ID %d)',
                class_basename($operationClass),
                $operation->getKey()
            ),
            'data' => [[
                'operation_id' => $operation->getKey(),
                'operation' => $operationClass,
                'attributes' => $attributes,
                'output' => $operation->output ?? null,
            ]],
        ];
    }

    protected function normalizeAttributes($attributes): array
    {
        if (is_array($attributes) && Arr::isAssoc($attributes)) {
            return $attributes;
        }

        if (! is_array($attributes)) {
            return [];
        }

        $assoc = [];
        foreach ($attributes as $pair) {
            if (! is_array($pair)) {
                continue;
            }

            $key = $pair['key'] ?? null;
            if ($key === null || $key === '') {
                continue;
            }

            $assoc[$key] = $pair['value'] ?? null;
        }

        return $assoc;
    }
}


