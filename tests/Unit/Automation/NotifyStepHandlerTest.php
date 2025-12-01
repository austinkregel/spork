<?php

declare(strict_types=1);

namespace Tests\Unit\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\Steps\NotifyStepHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifyStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_notification()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Notify A',
            'enabled' => true,
        ]);

        $step = new AutomationStep([
            'type' => 'notify',
            'config' => [
                'title' => 'Hello',
                'message' => 'World',
                'level' => 'info',
                'user_ids' => [$this->user->id],
            ],
        ]);

        $handler = new NotifyStepHandler();
        $res = $handler->execute($automation, $step);
        $this->assertSame(['output' => 'Notifications sent: 1'], $res);
    }
}


