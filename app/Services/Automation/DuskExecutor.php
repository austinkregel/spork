<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;

class DuskExecutor
{
    /**
     * Execute Dusk steps for an automation.
     *
     * For MVP, we validate steps and record a structured output.
     * Browser execution can be wired via laravel-console-dusk later;
     * tests should mock this class where needed.
     *
     * @param  AutomationStep[]  $steps
     * @return array{output?:string,error?:string}
     */
    public function execute(Automation $automation, array $steps): array
    {
        $lines = [];
        foreach ($steps as $step) {
            if ($step->type !== 'dusk') {
                return ['error' => "Unsupported step type: {$step->type}"];
            }
            $config = $step->config ?? [];
            $action = $config['action'] ?? null;
            if (! $action) {
                return ['error' => "Missing action for step {$step->order}"];
            }
            $lines[] = sprintf(
                '[automation:%d] step:%d action:%s',
                $automation->getKey(),
                $step->order,
                $action
            );
        }

        return ['output' => implode("\n", $lines)];
    }
}
