<?php

declare(strict_types=1);

namespace Tests\Unit\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\Steps\OperationStepHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Operations\TestEchoOperation;
use Tests\TestCase;

class OperationStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_operations', function (Blueprint $table) {
            $table->id();
            $table->string('payload')->nullable();
            $table->text('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('should_run_at')->nullable();
            $table->timestamp('started_run_at')->nullable();
            $table->timestamp('finished_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        TestEchoOperation::$payloads = [];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_operations');
        parent::tearDown();
    }

    public function test_runs_operation_immediately()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Op',
            'enabled' => true,
            'cron_expression' => '* * * * *',
            'timezone' => 'UTC',
            'pacing_per_host_ms' => 1000,
            'max_concurrency' => 1,
        ]);
        $step = new AutomationStep([
            'config' => [
                'operation' => TestEchoOperation::class,
                'attributes' => [
                    ['key' => 'payload', 'value' => 'hello'],
                ],
            ],
        ]);

        $handler = new OperationStepHandler();
        $result = $handler->execute($automation, $step);

        $this->assertSame('Operation TestEchoOperation executed (ID 1)', $result['output']);
        $this->assertSame([['operation_id' => 1, 'operation' => TestEchoOperation::class, 'attributes' => ['payload' => 'hello'], 'output' => 'hello']], $result['data']);
        $this->assertSame(['hello'], TestEchoOperation::$payloads);
    }

    public function test_invalid_class_returns_error()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Op',
            'enabled' => true,
            'cron_expression' => '* * * * *',
            'timezone' => 'UTC',
            'pacing_per_host_ms' => 1000,
            'max_concurrency' => 1,
        ]);
        $step = new AutomationStep(['config' => ['operation' => 'Foo\\Bar']]);

        $handler = new OperationStepHandler();
        $result = $handler->execute($automation, $step);

        $this->assertSame('Provided class is not a valid Operation', $result['error']);
    }
}


