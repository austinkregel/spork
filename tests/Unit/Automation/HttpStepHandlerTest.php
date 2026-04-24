<?php

declare(strict_types=1);

namespace Tests\Unit\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\Steps\HttpStepHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_request_returns_json_payload()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Http',
            'enabled' => true,
        ]);
        $step = new AutomationStep([
            'config' => [
                'method' => 'GET',
                'url' => 'https://api.example.com/test',
                'timeout_ms' => 2000,
            ],
        ]);

        Http::fake([
            'https://api.example.com/test' => Http::response(['ok' => true], 200),
        ]);

        $handler = app(HttpStepHandler::class);
        $res = $handler->execute($automation, $step);

        $this->assertSame('GET https://api.example.com/test => 200', $res['output']);
        $this->assertSame([['ok' => true]], $res['data']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/test';
        });
    }

    public function test_invalid_url_returns_error()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Http',
            'enabled' => true,
        ]);
        $step = new AutomationStep(['config' => ['method' => 'GET', 'url' => 'foo']]);

        $handler = app(HttpStepHandler::class);
        $res = $handler->execute($automation, $step);

        $this->assertSame('HTTP step requires a valid URL', $res['error']);
    }
}
