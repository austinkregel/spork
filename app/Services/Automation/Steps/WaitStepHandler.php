<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Models\Automation;
use App\Models\AutomationStep;

class WaitStepHandler
{
    protected const MAX_MS = 300000; // 5 minutes safeguard

    /**
     * @return array{output?:string,error?:string}
     */
    public function execute(Automation $automation, AutomationStep $step): array
    {
        $config = $step->config ?? [];
        $ms = (int) ($config['ms'] ?? 0);

        if ($ms < 0) {
            return ['error' => 'Wait duration must be positive'];
        }

        $ms = min($ms, self::MAX_MS);

        if ($ms > 0) {
            usleep($ms * 1000);
        }

        return ['output' => sprintf('Waited %d ms', $ms)];
    }
}


