<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spork\Servers\Manage;

use App\Actions\Spork\Servers\Manage\StartAction;
use App\Models\Server;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class StartActionTest extends TestCase
{
    public function test_start_method(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with(['items' => 'required|array']);
        $request->shouldReceive('input')->once()->with('items')->andReturn([1, 2, 3]);

        $server = Mockery::mock(Server::class);
        $server->shouldReceive('whereIn')->once()->with('id', [1, 2, 3])->andReturnSelf();
        $server->shouldReceive('get')->once()->andReturn(collect([$server, $server, $server]));

        $action = new StartAction();
        $action($request);
    }
}
