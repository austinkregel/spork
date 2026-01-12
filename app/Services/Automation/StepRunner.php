<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\Steps\ConditionStepHandler;
use App\Services\Automation\Steps\HttpStepHandler;
use App\Services\Automation\Steps\NotifyStepHandler;
use App\Services\Automation\Steps\OperationStepHandler;
use App\Services\Automation\Steps\SshStepHandler;
use App\Services\Automation\Steps\TaggingStepHandler;
use App\Services\Automation\Steps\WaitStepHandler;
use Illuminate\Support\Arr;

class StepRunner
{
    public function __construct(
        protected DuskExecutor $duskExecutor,
        protected HttpStepHandler $httpHandler,
        protected SshStepHandler $sshHandler,
        protected TaggingStepHandler $taggingHandler,
        protected NotifyStepHandler $notifyHandler,
        protected WaitStepHandler $waitHandler,
        protected ConditionStepHandler $conditionHandler,
        protected OperationStepHandler $operationHandler,
    ) {}

    /**
     * @param  AutomationStep[]  $steps
     * @return array{output?:string,error?:string}
     */
    public function run(Automation $automation, array $steps): array
    {
        $context = new StepContext($automation);
        $outputs = [];

        foreach ($steps as $index => $step) {
            $type = strtolower((string) $step->type);

            $result = match ($type) {
                'dusk' => $this->duskExecutor->execute($automation, [$step]),
                'http' => $this->httpHandler->execute($automation, $step),
                'ssh' => $this->sshHandler->execute($automation, $step),
                'tagging' => $this->taggingHandler->execute($automation, $step),
                'notify' => $this->notifyHandler->execute($automation, $step),
                'wait' => $this->waitHandler->execute($automation, $step),
                'condition' => $this->conditionHandler->execute($automation, $step, $context),
                'operation' => $this->operationHandler->execute($automation, $step),
                default => ['error' => "Unsupported step type: {$step->type}"],
            };

            if (isset($result['error'])) {
                return $result;
            }

            if (isset($result['output'])) {
                $outputs[] = $result['output'];
            }

            $context->record($index, $result['data'] ?? []);

            if (! empty($result['skip'])) {
                break;
            }
        }

        return ['output' => implode("\n", Arr::wrap($outputs))];
    }
}
