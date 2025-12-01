<?php

declare(strict_types=1);

namespace Tests\Unit\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Server;
use App\Services\Automation\Steps\SshStepHandler;
use App\Services\SshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SshStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runs_command_and_returns_output()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'SSH A',
            'enabled' => true,
        ]);
        $server = Server::factory()->create(['internal_ip_address' => '127.0.0.1']);
        $step = new AutomationStep([
            'type' => 'ssh',
            'config' => [
                'server_id' => $server->id,
                'command' => 'echo test',
            ],
        ]);

        $ssh = Mockery::mock(SshService::class);
        $ssh->shouldReceive('run')->andReturn(['stdout' => 'ok', 'stderr' => '']);
        $handler = new SshStepHandler($ssh);

        $res = $handler->execute($automation, $step);
        $this->assertSame(['output' => 'ok'], $res);
    }
}


