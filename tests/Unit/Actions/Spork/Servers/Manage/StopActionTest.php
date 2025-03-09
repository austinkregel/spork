<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spork\Servers\Manage;

use App\Actions\Spork\Servers\Manage\StopAction;
use App\Models\Server;
use App\Services\SshService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class StopActionTest extends TestCase
{
    public function test_stop_method(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with(['items' => 'required|array']);
        $request->shouldReceive('input')->once()->with('items')->andReturn([1, 2, 3]);

        $server = Mockery::mock(Server::class);
        $server->shouldReceive('whereIn')->once()->with('id', [1, 2, 3])->andReturnSelf();
        $server->shouldReceive('get')->once()->andReturn(collect([$server, $server, $server]));

        $sshService = Mockery::mock(SshService::class);
        $sshService->shouldReceive('execute')->times(3)->with('shutdown -h');

        $action = new StopAction();
        $action($request);
    }
}
