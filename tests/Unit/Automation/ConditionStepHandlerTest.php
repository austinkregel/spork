<?php

declare(strict_types=1);

namespace Tests\Unit\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\StepContext;
use App\Services\Automation\Steps\ConditionStepHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_pass()
    {
        $this->actingAsUser();
        $handler = new ConditionStepHandler();

        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Cond',
            'enabled' => true,
        ]);
        $context = new StepContext($automation);
        $context->record(0, [['foo' => 'bar']]);
        $step = new AutomationStep(['config' => [
            'conditions' => [
                ['parameter' => 'foo', 'comparator' => 'EQUALS', 'value' => 'bar'],
            ],
            'source' => 'step:0',
        ]]);

        $res = $handler->execute($automation, $step, $context);
        $this->assertSame(['output' => 'Conditions passed for 1 item(s)', 'data' => [['foo' => 'bar']]], $res);
    }

    public function test_fail_skip()
    {
        $this->actingAsUser();
        $handler = new ConditionStepHandler();

        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Cond',
            'enabled' => true,
        ]);
        $context = new StepContext($automation);
        $context->record(0, [['foo' => 'bar']]);
        $step = new AutomationStep(['config' => [
            'conditions' => [
                ['parameter' => 'foo', 'comparator' => 'NOT_EQUAL', 'value' => 'bar'],
            ],
            'on_false' => 'skip',
            'source' => 'step:0',
        ]]);

        $res = $handler->execute($automation, $step, $context);
        $this->assertSame(['output' => 'Conditions failed; skipping remaining steps', 'skip' => true, 'data' => []], $res);
    }

    public function test_fail_abort()
    {
        $this->actingAsUser();
        $handler = new ConditionStepHandler();

        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Cond',
            'enabled' => true,
        ]);
        $context = new StepContext($automation);
        $context->record(0, [['foo' => 'bar']]);
        $step = new AutomationStep(['config' => [
            'conditions' => [
                ['parameter' => 'foo', 'comparator' => 'NOT_EQUAL', 'value' => 'bar'],
            ],
            'on_false' => 'fail',
            'source' => 'step:0',
        ]]);

        $res = $handler->execute($automation, $step, $context);
        $this->assertSame(['error' => 'Conditions failed'], $res);
    }
}


