<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spork\Servers\Manage;

use App\Actions\Spork\Servers\Manage\RestartAction;
use App\Models\Server;
use App\Services\SshService;
use Mockery;
use Tests\TestCase;

class RestartActionTest extends TestCase
{
    public function test_restart_method(): void
    {
        $server = Mockery::mock(Server::class);
        $sshService = Mockery::mock(SshService::class);

        $server->shouldReceive('getAttribute')->with('internal_ip_address')->andReturn('127.0.0.1');
        $server->shouldReceive('getAttribute')->with('credential')->andReturn((object) [
            'settings' => ['username' => 'root'],
            'getPublicKey' => '/path/to/public/key',
            'getPrivateKey' => '/path/to/private/key',
            'getPasskey' => 'passkey',
        ]);

        $sshService->shouldReceive('run')->with('sudo reboot')->andReturn([
            'stdout' => 'Rebooting...',
            'stderr' => '',
        ]);

        $action = new RestartAction($server, $sshService);
        $result = $action->restart();

        $this->assertSame('Rebooting...', $result['stdout']);
        $this->assertSame('', $result['stderr']);
    }
}
