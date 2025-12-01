<?php

declare(strict_types=1);

namespace Tests\Unit\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\Steps\WaitStepHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_waits_and_reports()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Waiter',
            'enabled' => true,
        ]);

        $step = new AutomationStep([
            'type' => 'wait',
            'config' => ['ms' => 5],
        ]);

        $handler = new WaitStepHandler();
        $res = $handler->execute($automation, $step);

        $this->assertSame(['output' => 'Waited 5 ms'], $res);
    }
}


